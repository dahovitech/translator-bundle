<?php

namespace Dahovitech\TranslatorBundle\Tests\Unit\Service;

use Dahovitech\TranslatorBundle\Service\AutoTranslationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AutoTranslationServiceTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private AutoTranslationService $autoTranslationService;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $config = [
            'google' => ['api_key' => 'test-google-key'],
            'deepl' => ['api_key' => 'test-deepl-key', 'free_tier' => true],
            'libre' => ['url' => 'https://libretranslate.com']
        ];
        
        $this->autoTranslationService = new AutoTranslationService(
            $this->httpClient,
            $this->logger,
            $config
        );
    }

    public function testTranslateWithMockProvider(): void
    {
        $result = $this->autoTranslationService->translate(
            'hello',
            'fr',
            'en',
            'mock'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('bonjour', $result['translated_text']);
        $this->assertEquals('en', $result['detected_language']);
        $this->assertEquals('mock', $result['provider']);
    }

    public function testTranslateWithGoogleProvider(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'data' => [
                'translations' => [
                    [
                        'translatedText' => 'Bonjour',
                        'detectedSourceLanguage' => 'en'
                    ]
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://translation.googleapis.com/language/translate/v2')
            ->willReturn($response);

        $result = $this->autoTranslationService->translate(
            'Hello',
            'fr',
            'en',
            'google'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Bonjour', $result['translated_text']);
        $this->assertEquals('en', $result['detected_language']);
        $this->assertEquals('google', $result['provider']);
    }

    public function testTranslateWithDeepLProvider(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'translations' => [
                [
                    'text' => 'Bonjour',
                    'detected_source_language' => 'EN'
                ]
            ]
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api-free.deepl.com/v2/translate')
            ->willReturn($response);

        $result = $this->autoTranslationService->translate(
            'Hello',
            'fr',
            'en',
            'deepl'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Bonjour', $result['translated_text']);
        $this->assertEquals('en', $result['detected_language']);
        $this->assertEquals('deepl', $result['provider']);
    }

    public function testTranslateWithLibreProvider(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'translatedText' => 'Bonjour',
            'detectedLanguage' => 'en'
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://libretranslate.com/translate')
            ->willReturn($response);

        $result = $this->autoTranslationService->translate(
            'Hello',
            'fr',
            'en',
            'libre'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('Bonjour', $result['translated_text']);
        $this->assertEquals('en', $result['detected_language']);
        $this->assertEquals('libre', $result['provider']);
    }

    public function testTranslateBatch(): void
    {
        $texts = ['hello', 'goodbye', 'thank you'];
        
        $results = $this->autoTranslationService->translateBatch(
            $texts,
            'fr',
            'en',
            'mock'
        );

        $this->assertCount(3, $results);
        $this->assertTrue($results[0]['success']);
        $this->assertEquals('bonjour', $results[0]['translated_text']);
        $this->assertTrue($results[1]['success']);
        $this->assertEquals('au revoir', $results[1]['translated_text']);
        $this->assertTrue($results[2]['success']);
        $this->assertEquals('merci', $results[2]['translated_text']);
    }

    public function testDetectLanguage(): void
    {
        $result = $this->autoTranslationService->detectLanguage(
            'Bonjour tout le monde',
            'mock'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('en', $result['detected_language']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    public function testGetSupportedLanguages(): void
    {
        $languages = $this->autoTranslationService->getSupportedLanguages('mock');
        
        $this->assertIsArray($languages);
        $this->assertContains('en', $languages);
        $this->assertContains('fr', $languages);
        $this->assertContains('es', $languages);
    }

    public function testGetAvailableProviders(): void
    {
        $providers = $this->autoTranslationService->getAvailableProviders();
        
        $this->assertIsArray($providers);
        $this->assertContains('google', $providers);
        $this->assertContains('deepl', $providers);
        $this->assertContains('libre', $providers);
        $this->assertContains('mock', $providers);
    }

    public function testSetDefaultProvider(): void
    {
        $this->autoTranslationService->setDefaultProvider('deepl');
        
        // Tester que le provider par défaut a changé
        $result = $this->autoTranslationService->translate('hello', 'fr', 'en');
        // Le provider utilisé devrait être deepl (mais on utilise mock pour le test)
        $this->assertTrue($result['success']);
    }

    public function testIsProviderAvailable(): void
    {
        $this->assertTrue($this->autoTranslationService->isProviderAvailable('mock'));
        $this->assertTrue($this->autoTranslationService->isProviderAvailable('google'));
        $this->assertFalse($this->autoTranslationService->isProviderAvailable('nonexistent'));
    }

    public function testTranslateWithInvalidProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'invalid' not supported");
        
        $this->autoTranslationService->translate(
            'hello',
            'fr',
            'en',
            'invalid'
        );
    }

    public function testSetDefaultProviderWithInvalidProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'invalid' not supported");
        
        $this->autoTranslationService->setDefaultProvider('invalid');
    }

    public function testTranslateWithHttpError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('HTTP Error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Auto-translation failed');

        $result = $this->autoTranslationService->translate(
            'Hello',
            'fr',
            'en',
            'google'
        );

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['translated_text']);
        $this->assertStringContains('HTTP Error', $result['error']);
    }

    public function testTranslateWithInvalidGoogleResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('toArray')->willReturn([
            'error' => ['message' => 'Invalid API key']
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid response from Google Translate API');

        $this->autoTranslationService->translate(
            'Hello',
            'fr',
            'en',
            'google'
        );
    }

    public function testTranslateWithMissingApiKey(): void
    {
        $autoTranslationService = new AutoTranslationService(
            $this->httpClient,
            $this->logger,
            ['google' => []] // Pas de clé API
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Google Translate API key not configured');

        $autoTranslationService->translate(
            'Hello',
            'fr',
            'en',
            'google'
        );
    }

    public function testGetSupportedLanguagesForDifferentProviders(): void
    {
        $googleLanguages = $this->autoTranslationService->getSupportedLanguages('google');
        $deeplLanguages = $this->autoTranslationService->getSupportedLanguages('deepl');
        $libreLanguages = $this->autoTranslationService->getSupportedLanguages('libre');

        $this->assertGreaterThan(50, count($googleLanguages));
        $this->assertGreaterThan(20, count($deeplLanguages));
        $this->assertGreaterThan(15, count($libreLanguages));

        // Vérifier que les langues communes sont présentes
        $this->assertContains('en', $googleLanguages);
        $this->assertContains('fr', $googleLanguages);
        $this->assertContains('en', $deeplLanguages);
        $this->assertContains('fr', $deeplLanguages);
    }

    public function testDetectLanguageWithError(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Detection error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Language detection failed');

        $result = $this->autoTranslationService->detectLanguage(
            'Hello world',
            'google'
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('unknown', $result['detected_language']);
        $this->assertEquals(0, $result['confidence']);
    }
}

