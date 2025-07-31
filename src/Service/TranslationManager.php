<?php

namespace Dahovitech\TranslatorBundle\Service;

use Dahovitech\TranslatorBundle\Entity\Translation;
use Dahovitech\TranslatorBundle\Repository\TranslationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslationRepository $translationRepository,
        private TranslatorInterface $translator,
        private ?CacheManager $cacheManager = null,
        private bool $enableCache = true,
        private ?FileFormatManager $fileFormatManager = null,
        private ?AutoTranslationService $autoTranslationService = null
    ) {
    }

    /**
     * Crée ou met à jour une traduction
     */
    public function setTranslation(string $key, string $locale, string $content, string $domain = 'messages'): Translation
    {
        $translation = $this->translationRepository->findByKeyLocaleAndDomain($key, $locale, $domain);

        if (!$translation) {
            $translation = new Translation();
            $translation->setTranslationKey($key);
            $translation->setLocale($locale);
            $translation->setDomain($domain);
        }

        $translation->setContent($content);

        $this->entityManager->persist($translation);
        $this->entityManager->flush();

        // Mise à jour du cache
        if ($this->enableCache && $this->cacheManager) {
            $this->cacheManager->cacheTranslationWithTags($key, $locale, $domain, $content);
        }

        return $translation;
    }

    /**
     * Récupère une traduction
     */
    public function getTranslation(string $key, string $locale, string $domain = 'messages'): ?string
    {
        // Vérifier d'abord le cache
        if ($this->enableCache && $this->cacheManager) {
            $cachedContent = $this->cacheManager->getCachedTranslation($key, $locale, $domain);
            if ($cachedContent !== null) {
                return $cachedContent;
            }
        }

        // Si pas en cache, récupérer depuis la base de données
        $translation = $this->translationRepository->findByKeyLocaleAndDomain($key, $locale, $domain);
        
        if ($translation) {
            $content = $translation->getContent();
            
            // Mettre en cache pour les prochaines fois
            if ($this->enableCache && $this->cacheManager) {
                $this->cacheManager->cacheTranslationWithTags($key, $locale, $domain, $content);
            }
            
            return $content;
        }
        
        return null;
    }

    /**
     * Supprime une traduction
     */
    public function removeTranslation(string $key, string $locale, string $domain = 'messages'): bool
    {
        $translation = $this->translationRepository->findByKeyLocaleAndDomain($key, $locale, $domain);

        if ($translation) {
            $this->entityManager->remove($translation);
            $this->entityManager->flush();
            
            // Invalider le cache
            if ($this->enableCache && $this->cacheManager) {
                $this->cacheManager->invalidateTranslation($key, $locale, $domain);
            }
            
            return true;
        }

        return false;
    }

    /**
     * Importe des traductions depuis un tableau
     */
    public function importTranslations(array $translations, string $locale, string $domain = 'messages'): int
    {
        $count = 0;
        
        foreach ($translations as $key => $content) {
            if (is_string($content)) {
                $this->setTranslation($key, $locale, $content, $domain);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Exporte toutes les traductions pour une locale
     */
    public function exportTranslations(string $locale, string $domain = 'messages'): array
    {
        $translations = $this->translationRepository->createQueryBuilder('t')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.domain = :domain')
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->getTranslationKey()] = $translation->getContent();
        }

        return $result;
    }

    /**
     * Trouve les traductions manquantes
     */
    public function findMissingTranslations(string $locale, string $referenceLocale = 'en'): array
    {
        return $this->translationRepository->findMissingTranslations($locale, $referenceLocale);
    }

    /**
     * Obtient toutes les locales disponibles
     */
    public function getAvailableLocales(): array
    {
        return $this->translationRepository->findAvailableLocales();
    }

    /**
     * Obtient toutes les clés de traduction
     */
    public function getTranslationKeys(): array
    {
        return $this->translationRepository->findUniqueKeys();
    }

    /**
     * Traduit un texte en utilisant le service de traduction Symfony
     */
    public function translate(string $key, array $parameters = [], string $domain = 'messages', string $locale = null): string
    {
        return $this->translator->trans($key, $parameters, $domain, $locale);
    }

    /**
     * Vérifie si une traduction existe
     */
    public function hasTranslation(string $key, string $locale, string $domain = 'messages'): bool
    {
        return $this->translationRepository->findByKeyLocaleAndDomain($key, $locale, $domain) !== null;
    }
}


    /**
     * Vide le cache des traductions
     */
    public function clearCache(): bool
    {
        if ($this->enableCache && $this->cacheManager) {
            return $this->cacheManager->clearCache();
        }
        
        return false;
    }

    /**
     * Invalide le cache pour une locale spécifique
     */
    public function invalidateLocaleCache(string $locale): bool
    {
        if ($this->enableCache && $this->cacheManager) {
            return $this->cacheManager->invalidateLocale($locale);
        }
        
        return false;
    }

    /**
     * Invalide le cache pour un domaine spécifique
     */
    public function invalidateDomainCache(string $domain): bool
    {
        if ($this->enableCache && $this->cacheManager) {
            return $this->cacheManager->invalidateDomain($domain);
        }
        
        return false;
    }

    /**
     * Précharge le cache avec toutes les traductions d'une locale
     */
    public function preloadCache(string $locale, string $domain = 'messages'): int
    {
        if (!$this->enableCache || !$this->cacheManager) {
            return 0;
        }

        $translations = $this->exportTranslations($locale, $domain);
        $this->cacheManager->preloadCache($locale, $domain, $translations);
        
        return count($translations);
    }

    /**
     * Obtient les statistiques du cache
     */
    public function getCacheStats(): array
    {
        if ($this->enableCache && $this->cacheManager) {
            return $this->cacheManager->getCacheStats();
        }
        
        return ['cache_enabled' => false];
    }

    /**
     * Active ou désactive le cache
     */
    public function setCacheEnabled(bool $enabled): void
    {
        $this->enableCache = $enabled;
    }

    /**
     * Vérifie si le cache est activé
     */
    public function isCacheEnabled(): bool
    {
        return $this->enableCache && $this->cacheManager !== null;
    }


    /**
     * Importe des traductions depuis un fichier
     */
    public function importFromFile(string $filePath, string $locale, string $domain = 'messages', bool $overwrite = false): int
    {
        if (!$this->fileFormatManager) {
            throw new \RuntimeException('FileFormatManager non disponible');
        }

        $translations = $this->fileFormatManager->importFromFile($filePath);
        $imported = 0;

        foreach ($translations as $key => $content) {
            if (!$overwrite && $this->hasTranslation($key, $locale, $domain)) {
                continue; // Skip existing translations if overwrite is false
            }

            $this->setTranslation($key, $locale, $content, $domain);
            $imported++;
        }

        return $imported;
    }

    /**
     * Exporte des traductions vers un fichier
     */
    public function exportToFile(string $filePath, string $locale, string $domain = 'messages', string $format = null): bool
    {
        if (!$this->fileFormatManager) {
            throw new \RuntimeException('FileFormatManager non disponible');
        }

        $translations = $this->exportTranslations($locale, $domain);
        return $this->fileFormatManager->exportToFile($translations, $filePath, $format);
    }

    /**
     * Importe des traductions depuis plusieurs fichiers
     */
    public function importFromFiles(array $filePaths, string $locale, string $domain = 'messages', bool $overwrite = false): array
    {
        $results = [];

        foreach ($filePaths as $filePath) {
            try {
                $imported = $this->importFromFile($filePath, $locale, $domain, $overwrite);
                $results[$filePath] = ['success' => true, 'imported' => $imported];
            } catch (\Exception $e) {
                $results[$filePath] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Exporte des traductions vers plusieurs formats
     */
    public function exportToMultipleFormats(string $basePath, string $locale, string $domain = 'messages', array $formats = ['yaml', 'json', 'xliff']): array
    {
        if (!$this->fileFormatManager) {
            throw new \RuntimeException('FileFormatManager non disponible');
        }

        $results = [];
        $translations = $this->exportTranslations($locale, $domain);

        foreach ($formats as $format) {
            $filePath = $basePath . '.' . $format;
            try {
                $success = $this->fileFormatManager->exportToFile($translations, $filePath, $format);
                $results[$format] = ['success' => $success, 'file' => $filePath];
            } catch (\Exception $e) {
                $results[$format] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Valide un fichier de traduction
     */
    public function validateTranslationFile(string $filePath, string $format = null): array
    {
        if (!$this->fileFormatManager) {
            throw new \RuntimeException('FileFormatManager non disponible');
        }

        return $this->fileFormatManager->validateFile($filePath, $format);
    }

    /**
     * Convertit un fichier de traduction d'un format vers un autre
     */
    public function convertTranslationFile(string $sourcePath, string $targetPath, string $sourceFormat = null, string $targetFormat = null): bool
    {
        if (!$this->fileFormatManager) {
            throw new \RuntimeException('FileFormatManager non disponible');
        }

        return $this->fileFormatManager->convertFile($sourcePath, $targetPath, $sourceFormat, $targetFormat);
    }

    /**
     * Obtient les formats de fichiers supportés
     */
    public function getSupportedFileFormats(): array
    {
        if (!$this->fileFormatManager) {
            return [];
        }

        return $this->fileFormatManager->getSupportedFormats();
    }

    /**
     * Synchronise les traductions avec un répertoire de fichiers
     */
    public function syncWithDirectory(string $directory, string $locale, string $domain = 'messages', bool $bidirectional = false): array
    {
        if (!$this->fileFormatManager) {
            throw new \RuntimeException('FileFormatManager non disponible');
        }

        $results = ['imported' => 0, 'exported' => 0, 'errors' => []];

        if (!is_dir($directory)) {
            $results['errors'][] = "Le répertoire {$directory} n'existe pas";
            return $results;
        }

        // Import des fichiers existants
        $files = glob($directory . '/*.' . '{yaml,yml,json,xliff,xlf,php}', GLOB_BRACE);
        foreach ($files as $file) {
            try {
                $imported = $this->importFromFile($file, $locale, $domain, true);
                $results['imported'] += $imported;
            } catch (\Exception $e) {
                $results['errors'][] = "Erreur lors de l'import de {$file}: " . $e->getMessage();
            }
        }

        // Export vers le répertoire si bidirectionnel
        if ($bidirectional) {
            $exportPath = $directory . "/messages.{$locale}.yaml";
            try {
                if ($this->exportToFile($exportPath, $locale, $domain)) {
                    $results['exported'] = count($this->exportTranslations($locale, $domain));
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Erreur lors de l'export vers {$exportPath}: " . $e->getMessage();
            }
        }

        return $results;
    }


    /**
     * Traduit automatiquement une clé vers une locale cible
     */
    public function autoTranslate(string $key, string $sourceLocale, string $targetLocale, string $domain = 'messages', ?string $provider = null): ?string
    {
        if (!$this->autoTranslationService) {
            throw new \RuntimeException('AutoTranslationService non disponible');
        }

        // Récupérer le texte source
        $sourceText = $this->getTranslation($key, $sourceLocale, $domain);
        
        if (!$sourceText) {
            throw new \InvalidArgumentException("Traduction source non trouvée pour la clé '{$key}' en {$sourceLocale}");
        }

        // Traduire automatiquement
        $result = $this->autoTranslationService->translate($sourceText, $targetLocale, $sourceLocale, $provider);
        
        if ($result['success']) {
            // Sauvegarder la traduction automatique
            $this->setTranslation($key, $targetLocale, $result['translated_text'], $domain);
            return $result['translated_text'];
        }

        return null;
    }

    /**
     * Traduit automatiquement toutes les clés manquantes pour une locale
     */
    public function autoTranslateMissingKeys(string $sourceLocale, string $targetLocale, string $domain = 'messages', ?string $provider = null): array
    {
        if (!$this->autoTranslationService) {
            throw new \RuntimeException('AutoTranslationService non disponible');
        }

        $missingKeys = $this->findMissingTranslations($targetLocale, $sourceLocale);
        $results = [];

        foreach ($missingKeys as $keyInfo) {
            $key = $keyInfo['key'];
            
            try {
                $translatedText = $this->autoTranslate($key, $sourceLocale, $targetLocale, $domain, $provider);
                $results[$key] = [
                    'success' => true,
                    'translated_text' => $translatedText
                ];
            } catch (\Exception $e) {
                $results[$key] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }

            // Petite pause pour éviter de surcharger l'API
            usleep(200000); // 200ms
        }

        return $results;
    }

    /**
     * Traduit automatiquement un lot de traductions
     */
    public function autoTranslateBatch(array $translations, string $sourceLocale, string $targetLocale, string $domain = 'messages', ?string $provider = null): array
    {
        if (!$this->autoTranslationService) {
            throw new \RuntimeException('AutoTranslationService non disponible');
        }

        $texts = array_values($translations);
        $keys = array_keys($translations);
        
        $results = $this->autoTranslationService->translateBatch($texts, $targetLocale, $sourceLocale, $provider);
        
        $translatedKeys = [];
        foreach ($results as $index => $result) {
            $key = $keys[$index];
            
            if ($result['success']) {
                $this->setTranslation($key, $targetLocale, $result['translated_text'], $domain);
                $translatedKeys[$key] = $result['translated_text'];
            }
        }

        return $translatedKeys;
    }

    /**
     * Détecte la langue d'une traduction
     */
    public function detectLanguage(string $key, string $locale, string $domain = 'messages', ?string $provider = null): array
    {
        if (!$this->autoTranslationService) {
            throw new \RuntimeException('AutoTranslationService non disponible');
        }

        $text = $this->getTranslation($key, $locale, $domain);
        
        if (!$text) {
            throw new \InvalidArgumentException("Traduction non trouvée pour la clé '{$key}' en {$locale}");
        }

        return $this->autoTranslationService->detectLanguage($text, $provider);
    }

    /**
     * Obtient les providers de traduction automatique disponibles
     */
    public function getAvailableTranslationProviders(): array
    {
        if (!$this->autoTranslationService) {
            return [];
        }

        return $this->autoTranslationService->getAvailableProviders();
    }

    /**
     * Vérifie si un provider de traduction est disponible
     */
    public function isTranslationProviderAvailable(string $provider): bool
    {
        if (!$this->autoTranslationService) {
            return false;
        }

        return $this->autoTranslationService->isProviderAvailable($provider);
    }

    /**
     * Obtient les langues supportées par un provider
     */
    public function getSupportedLanguages(?string $provider = null): array
    {
        if (!$this->autoTranslationService) {
            return [];
        }

        return $this->autoTranslationService->getSupportedLanguages($provider);
    }

    /**
     * Suggère des traductions automatiques pour une clé
     */
    public function suggestTranslations(string $key, string $sourceLocale, array $targetLocales, string $domain = 'messages', ?string $provider = null): array
    {
        if (!$this->autoTranslationService) {
            throw new \RuntimeException('AutoTranslationService non disponible');
        }

        $sourceText = $this->getTranslation($key, $sourceLocale, $domain);
        
        if (!$sourceText) {
            throw new \InvalidArgumentException("Traduction source non trouvée pour la clé '{$key}' en {$sourceLocale}");
        }

        $suggestions = [];
        
        foreach ($targetLocales as $targetLocale) {
            try {
                $result = $this->autoTranslationService->translate($sourceText, $targetLocale, $sourceLocale, $provider);
                
                $suggestions[$targetLocale] = [
                    'success' => $result['success'],
                    'suggested_text' => $result['translated_text'] ?? '',
                    'confidence' => $result['confidence'] ?? 0,
                    'provider' => $result['provider'] ?? $provider,
                    'existing_translation' => $this->getTranslation($key, $targetLocale, $domain)
                ];
            } catch (\Exception $e) {
                $suggestions[$targetLocale] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'existing_translation' => $this->getTranslation($key, $targetLocale, $domain)
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Valide une traduction automatique avant de la sauvegarder
     */
    public function validateAutoTranslation(string $originalText, string $translatedText, string $sourceLocale, string $targetLocale): array
    {
        $validation = [
            'valid' => true,
            'warnings' => [],
            'suggestions' => []
        ];

        // Vérifications de base
        if (empty($translatedText)) {
            $validation['valid'] = false;
            $validation['warnings'][] = 'Traduction vide';
        }

        if (strlen($translatedText) > strlen($originalText) * 3) {
            $validation['warnings'][] = 'Traduction anormalement longue';
        }

        if (strlen($translatedText) < strlen($originalText) / 3) {
            $validation['warnings'][] = 'Traduction anormalement courte';
        }

        // Vérifier si la traduction contient encore du texte dans la langue source
        if ($this->containsSourceLanguage($translatedText, $sourceLocale)) {
            $validation['warnings'][] = 'La traduction semble contenir du texte dans la langue source';
        }

        // Vérifier les placeholders et variables
        $originalPlaceholders = $this->extractPlaceholders($originalText);
        $translatedPlaceholders = $this->extractPlaceholders($translatedText);
        
        if (count($originalPlaceholders) !== count($translatedPlaceholders)) {
            $validation['warnings'][] = 'Nombre de placeholders différent entre l\'original et la traduction';
        }

        return $validation;
    }

    /**
     * Vérifie si un texte contient des mots dans une langue source
     */
    private function containsSourceLanguage(string $text, string $sourceLocale): bool
    {
        // Implémentation basique - peut être améliorée avec des dictionnaires
        $commonWords = [
            'en' => ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'],
            'fr' => ['le', 'la', 'les', 'et', 'ou', 'mais', 'dans', 'sur', 'à', 'pour', 'de', 'avec', 'par'],
            'es' => ['el', 'la', 'los', 'las', 'y', 'o', 'pero', 'en', 'sobre', 'a', 'para', 'de', 'con', 'por'],
            'de' => ['der', 'die', 'das', 'und', 'oder', 'aber', 'in', 'auf', 'zu', 'für', 'von', 'mit', 'durch']
        ];

        if (!isset($commonWords[$sourceLocale])) {
            return false;
        }

        $words = preg_split('/\s+/', strtolower($text));
        $sourceWords = $commonWords[$sourceLocale];
        
        $matches = array_intersect($words, $sourceWords);
        
        return count($matches) > 0;
    }

    /**
     * Extrait les placeholders d'un texte
     */
    private function extractPlaceholders(string $text): array
    {
        $placeholders = [];
        
        // Placeholders Symfony: %variable%
        preg_match_all('/%[^%]+%/', $text, $matches);
        $placeholders = array_merge($placeholders, $matches[0]);
        
        // Placeholders ICU: {variable}
        preg_match_all('/\{[^}]+\}/', $text, $matches);
        $placeholders = array_merge($placeholders, $matches[0]);
        
        // Placeholders sprintf: %s, %d, etc.
        preg_match_all('/%[sdcoxXeEfFgG]/', $text, $matches);
        $placeholders = array_merge($placeholders, $matches[0]);
        
        return array_unique($placeholders);
    }

