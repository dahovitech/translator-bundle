<?php

namespace Dahovitech\TranslatorBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AutoTranslationService
{
    private array $providers = [];
    private string $defaultProvider = 'google';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private array $config = []
    ) {
        $this->initializeProviders();
    }

    /**
     * Traduit un texte automatiquement
     */
    public function translate(string $text, string $targetLocale, string $sourceLocale = 'auto', ?string $provider = null): array
    {
        $provider = $provider ?? $this->defaultProvider;
        
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Provider '{$provider}' not supported");
        }

        $this->logger->info('Auto-translation request', [
            'provider' => $provider,
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'text_length' => strlen($text)
        ]);

        try {
            $result = $this->providers[$provider]($text, $targetLocale, $sourceLocale);
            
            $this->logger->info('Auto-translation completed', [
                'provider' => $provider,
                'success' => $result['success']
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Auto-translation failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'translated_text' => '',
                'detected_language' => null,
                'confidence' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traduit plusieurs textes en lot
     */
    public function translateBatch(array $texts, string $targetLocale, string $sourceLocale = 'auto', ?string $provider = null): array
    {
        $results = [];
        
        foreach ($texts as $key => $text) {
            $result = $this->translate($text, $targetLocale, $sourceLocale, $provider);
            $results[$key] = $result;
            
            // Petite pause pour éviter de surcharger l'API
            usleep(100000); // 100ms
        }

        return $results;
    }

    /**
     * Détecte la langue d'un texte
     */
    public function detectLanguage(string $text, ?string $provider = null): array
    {
        $provider = $provider ?? $this->defaultProvider;
        
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Provider '{$provider}' not supported");
        }

        try {
            // Pour la détection, on utilise une traduction vers la même langue
            $result = $this->providers[$provider]($text, 'en', 'auto');
            
            return [
                'success' => true,
                'detected_language' => $result['detected_language'] ?? 'unknown',
                'confidence' => $result['confidence'] ?? 0
            ];
        } catch (\Exception $e) {
            $this->logger->error('Language detection failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'detected_language' => 'unknown',
                'confidence' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtient les langues supportées par un provider
     */
    public function getSupportedLanguages(?string $provider = null): array
    {
        $provider = $provider ?? $this->defaultProvider;
        
        return match ($provider) {
            'google' => $this->getGoogleSupportedLanguages(),
            'deepl' => $this->getDeepLSupportedLanguages(),
            'libre' => $this->getLibreSupportedLanguages(),
            'mock' => ['en', 'fr', 'es', 'de', 'it'],
            default => []
        };
    }

    /**
     * Obtient les providers disponibles
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Définit le provider par défaut
     */
    public function setDefaultProvider(string $provider): void
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Provider '{$provider}' not supported");
        }
        
        $this->defaultProvider = $provider;
    }

    /**
     * Vérifie si un provider est configuré et disponible
     */
    public function isProviderAvailable(string $provider): bool
    {
        return isset($this->providers[$provider]) && $this->checkProviderConfiguration($provider);
    }

    /**
     * Initialise les providers de traduction
     */
    private function initializeProviders(): void
    {
        // Google Translate API
        if ($this->isConfigured('google')) {
            $this->providers['google'] = function (string $text, string $target, string $source) {
                return $this->translateWithGoogle($text, $target, $source);
            };
        }

        // DeepL API
        if ($this->isConfigured('deepl')) {
            $this->providers['deepl'] = function (string $text, string $target, string $source) {
                return $this->translateWithDeepL($text, $target, $source);
            };
        }

        // LibreTranslate (self-hosted)
        if ($this->isConfigured('libre')) {
            $this->providers['libre'] = function (string $text, string $target, string $source) {
                return $this->translateWithLibre($text, $target, $source);
            };
        }

        // Mock provider pour les tests
        $this->providers['mock'] = function (string $text, string $target, string $source) {
            return $this->translateWithMock($text, $target, $source);
        };
    }

    /**
     * Traduction avec Google Translate API
     */
    private function translateWithGoogle(string $text, string $target, string $source): array
    {
        $apiKey = $this->config['google']['api_key'] ?? null;
        
        if (!$apiKey) {
            throw new \RuntimeException('Google Translate API key not configured');
        }

        $url = 'https://translation.googleapis.com/language/translate/v2';
        $params = [
            'key' => $apiKey,
            'q' => $text,
            'target' => $target,
            'format' => 'text'
        ];

        if ($source !== 'auto') {
            $params['source'] = $source;
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'body' => $params
            ]);

            $data = $response->toArray();
            
            if (isset($data['data']['translations'][0])) {
                $translation = $data['data']['translations'][0];
                
                return [
                    'success' => true,
                    'translated_text' => $translation['translatedText'],
                    'detected_language' => $translation['detectedSourceLanguage'] ?? $source,
                    'confidence' => 1.0,
                    'provider' => 'google'
                ];
            }

            throw new \RuntimeException('Invalid response from Google Translate API');
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Google Translate API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Traduction avec DeepL API
     */
    private function translateWithDeepL(string $text, string $target, string $source): array
    {
        $apiKey = $this->config['deepl']['api_key'] ?? null;
        $isFree = $this->config['deepl']['free_tier'] ?? true;
        
        if (!$apiKey) {
            throw new \RuntimeException('DeepL API key not configured');
        }

        $baseUrl = $isFree ? 'https://api-free.deepl.com' : 'https://api.deepl.com';
        $url = $baseUrl . '/v2/translate';

        $params = [
            'auth_key' => $apiKey,
            'text' => $text,
            'target_lang' => strtoupper($target)
        ];

        if ($source !== 'auto') {
            $params['source_lang'] = strtoupper($source);
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'body' => $params
            ]);

            $data = $response->toArray();
            
            if (isset($data['translations'][0])) {
                $translation = $data['translations'][0];
                
                return [
                    'success' => true,
                    'translated_text' => $translation['text'],
                    'detected_language' => strtolower($translation['detected_source_language'] ?? $source),
                    'confidence' => 1.0,
                    'provider' => 'deepl'
                ];
            }

            throw new \RuntimeException('Invalid response from DeepL API');
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('DeepL API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Traduction avec LibreTranslate
     */
    private function translateWithLibre(string $text, string $target, string $source): array
    {
        $baseUrl = $this->config['libre']['url'] ?? 'https://libretranslate.com';
        $apiKey = $this->config['libre']['api_key'] ?? null;
        
        $url = rtrim($baseUrl, '/') . '/translate';
        
        $params = [
            'q' => $text,
            'source' => $source,
            'target' => $target,
            'format' => 'text'
        ];

        if ($apiKey) {
            $params['api_key'] = $apiKey;
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $params
            ]);

            $data = $response->toArray();
            
            if (isset($data['translatedText'])) {
                return [
                    'success' => true,
                    'translated_text' => $data['translatedText'],
                    'detected_language' => $data['detectedLanguage'] ?? $source,
                    'confidence' => 0.9,
                    'provider' => 'libre'
                ];
            }

            throw new \RuntimeException('Invalid response from LibreTranslate API');
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('LibreTranslate API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Provider mock pour les tests
     */
    private function translateWithMock(string $text, string $target, string $source): array
    {
        // Simulation d'une traduction
        $translations = [
            'hello' => ['fr' => 'bonjour', 'es' => 'hola', 'de' => 'hallo'],
            'goodbye' => ['fr' => 'au revoir', 'es' => 'adiós', 'de' => 'auf wiedersehen'],
            'thank you' => ['fr' => 'merci', 'es' => 'gracias', 'de' => 'danke']
        ];

        $lowerText = strtolower($text);
        $translatedText = $translations[$lowerText][$target] ?? "[MOCK] {$text} -> {$target}";

        return [
            'success' => true,
            'translated_text' => $translatedText,
            'detected_language' => $source === 'auto' ? 'en' : $source,
            'confidence' => 0.95,
            'provider' => 'mock'
        ];
    }

    /**
     * Vérifie si un provider est configuré
     */
    private function isConfigured(string $provider): bool
    {
        return isset($this->config[$provider]) && !empty($this->config[$provider]);
    }

    /**
     * Vérifie la configuration d'un provider
     */
    private function checkProviderConfiguration(string $provider): bool
    {
        return match ($provider) {
            'google' => !empty($this->config['google']['api_key'] ?? ''),
            'deepl' => !empty($this->config['deepl']['api_key'] ?? ''),
            'libre' => !empty($this->config['libre']['url'] ?? ''),
            'mock' => true,
            default => false
        };
    }

    /**
     * Langues supportées par Google Translate
     */
    private function getGoogleSupportedLanguages(): array
    {
        return [
            'af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs', 'bg', 'ca', 'ceb',
            'zh', 'co', 'hr', 'cs', 'da', 'nl', 'en', 'eo', 'et', 'fi', 'fr', 'fy', 'gl',
            'ka', 'de', 'el', 'gu', 'ht', 'ha', 'haw', 'he', 'hi', 'hmn', 'hu', 'is', 'ig',
            'id', 'ga', 'it', 'ja', 'jv', 'kn', 'kk', 'km', 'rw', 'ko', 'ku', 'ky', 'lo',
            'la', 'lv', 'lt', 'lb', 'mk', 'mg', 'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my',
            'ne', 'no', 'ny', 'or', 'ps', 'fa', 'pl', 'pt', 'pa', 'ro', 'ru', 'sm', 'gd',
            'sr', 'st', 'sn', 'sd', 'si', 'sk', 'sl', 'so', 'es', 'su', 'sw', 'sv', 'tl',
            'tg', 'ta', 'tt', 'te', 'th', 'tr', 'tk', 'uk', 'ur', 'ug', 'uz', 'vi', 'cy',
            'xh', 'yi', 'yo', 'zu'
        ];
    }

    /**
     * Langues supportées par DeepL
     */
    private function getDeepLSupportedLanguages(): array
    {
        return [
            'bg', 'cs', 'da', 'de', 'el', 'en', 'es', 'et', 'fi', 'fr', 'hu', 'id', 'it',
            'ja', 'ko', 'lt', 'lv', 'nb', 'nl', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sv',
            'tr', 'uk', 'zh'
        ];
    }

    /**
     * Langues supportées par LibreTranslate (exemple)
     */
    private function getLibreSupportedLanguages(): array
    {
        return [
            'en', 'ar', 'az', 'zh', 'cs', 'nl', 'eo', 'fi', 'fr', 'de', 'el', 'he', 'hi',
            'hu', 'id', 'ga', 'it', 'ja', 'ko', 'fa', 'pl', 'pt', 'ru', 'sk', 'es', 'sv',
            'tr', 'uk'
        ];
    }
}

