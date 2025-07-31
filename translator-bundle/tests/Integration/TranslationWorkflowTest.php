<?php

namespace Dahovitech\TranslatorBundle\Tests\Integration;

use Dahovitech\TranslatorBundle\Service\TranslationManager;
use Dahovitech\TranslatorBundle\Service\CacheManager;
use Dahovitech\TranslatorBundle\Service\VersionManager;
use Dahovitech\TranslatorBundle\Service\AutoTranslationService;
use Dahovitech\TranslatorBundle\Service\TranslationKeyDetector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TranslationWorkflowTest extends KernelTestCase
{
    private TranslationManager $translationManager;
    private CacheManager $cacheManager;
    private VersionManager $versionManager;
    private AutoTranslationService $autoTranslationService;
    private TranslationKeyDetector $keyDetector;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->translationManager = $container->get('dahovitech_translator.translation_manager');
        $this->cacheManager = $container->get('dahovitech_translator.cache_manager');
        $this->versionManager = $container->get('dahovitech_translator.version_manager');
        $this->autoTranslationService = $container->get('dahovitech_translator.auto_translation_service');
        $this->keyDetector = $container->get('dahovitech_translator.key_detector');
    }

    public function testCompleteTranslationWorkflow(): void
    {
        $key = 'integration.test.workflow';
        $locale = 'fr';
        $domain = 'messages';
        $content = 'Test d\'intégration complet';

        // 1. Créer une nouvelle traduction
        $translation = $this->translationManager->setTranslation($key, $locale, $content, $domain);
        $this->assertNotNull($translation);

        // 2. Vérifier que la traduction est récupérable
        $retrievedContent = $this->translationManager->getTranslation($key, $locale, $domain);
        $this->assertEquals($content, $retrievedContent);

        // 3. Vérifier que la traduction est mise en cache
        $cachedContent = $this->cacheManager->getCachedTranslation($key, $locale, $domain);
        $this->assertEquals($content, $cachedContent);

        // 4. Vérifier qu'une version a été créée
        $versions = $this->versionManager->getAllVersions($key, $locale, $domain);
        $this->assertCount(1, $versions);
        $this->assertEquals('create', $versions[0]->getChangeType());

        // 5. Modifier la traduction
        $updatedContent = 'Test d\'intégration modifié';
        $this->translationManager->setTranslation($key, $locale, $updatedContent, $domain);

        // 6. Vérifier que la modification est prise en compte
        $retrievedUpdatedContent = $this->translationManager->getTranslation($key, $locale, $domain);
        $this->assertEquals($updatedContent, $retrievedUpdatedContent);

        // 7. Vérifier qu'une nouvelle version a été créée
        $updatedVersions = $this->versionManager->getAllVersions($key, $locale, $domain);
        $this->assertCount(2, $updatedVersions);
        $this->assertEquals('update', $updatedVersions[1]->getChangeType());

        // 8. Tester la restauration d'une version précédente
        $restoredVersion = $this->versionManager->restoreVersion(
            $updatedVersions[0]->getId(),
            'Test de restauration'
        );
        $this->assertNotNull($restoredVersion);

        // 9. Vérifier que la restauration a fonctionné
        $restoredContent = $this->translationManager->getTranslation($key, $locale, $domain);
        $this->assertEquals($content, $restoredContent);

        // 10. Supprimer la traduction
        $deleted = $this->translationManager->removeTranslation($key, $locale, $domain);
        $this->assertTrue($deleted);

        // 11. Vérifier que la traduction n'existe plus
        $deletedContent = $this->translationManager->getTranslation($key, $locale, $domain);
        $this->assertNull($deletedContent);
    }

    public function testAutoTranslationWorkflow(): void
    {
        $sourceKey = 'auto.translation.test';
        $sourceLocale = 'en';
        $targetLocale = 'fr';
        $domain = 'messages';
        $sourceContent = 'Hello world';

        // 1. Créer la traduction source
        $this->translationManager->setTranslation($sourceKey, $sourceLocale, $sourceContent, $domain);

        // 2. Traduire automatiquement (utilise le provider mock)
        $translatedContent = $this->translationManager->autoTranslate(
            $sourceKey,
            $sourceLocale,
            $targetLocale,
            $domain,
            'mock'
        );

        $this->assertNotNull($translatedContent);
        $this->assertNotEquals($sourceContent, $translatedContent);

        // 3. Vérifier que la traduction automatique a été sauvegardée
        $savedTranslation = $this->translationManager->getTranslation($sourceKey, $targetLocale, $domain);
        $this->assertEquals($translatedContent, $savedTranslation);

        // 4. Vérifier qu'une version a été créée pour la traduction automatique
        $versions = $this->versionManager->getAllVersions($sourceKey, $targetLocale, $domain);
        $this->assertCount(1, $versions);
        $this->assertEquals('create', $versions[0]->getChangeType());
    }

    public function testBulkTranslationWorkflow(): void
    {
        $translations = [
            'bulk.test.key1' => 'First test content',
            'bulk.test.key2' => 'Second test content',
            'bulk.test.key3' => 'Third test content'
        ];
        $locale = 'en';
        $domain = 'messages';

        // 1. Importer les traductions en lot
        $importedCount = $this->translationManager->importTranslations($translations, $locale, $domain);
        $this->assertEquals(3, $importedCount);

        // 2. Vérifier que toutes les traductions ont été importées
        foreach ($translations as $key => $content) {
            $retrievedContent = $this->translationManager->getTranslation($key, $locale, $domain);
            $this->assertEquals($content, $retrievedContent);
        }

        // 3. Exporter les traductions
        $exportedTranslations = $this->translationManager->exportTranslations($locale, $domain);
        
        foreach ($translations as $key => $content) {
            $this->assertArrayHasKey($key, $exportedTranslations);
            $this->assertEquals($content, $exportedTranslations[$key]);
        }

        // 4. Traduire automatiquement vers une autre langue
        $targetLocale = 'fr';
        $autoTranslatedResults = $this->translationManager->autoTranslateBatch(
            $translations,
            $locale,
            $targetLocale,
            $domain,
            'mock'
        );

        $this->assertCount(3, $autoTranslatedResults);

        // 5. Vérifier que les traductions automatiques ont été sauvegardées
        foreach (array_keys($translations) as $key) {
            $translatedContent = $this->translationManager->getTranslation($key, $targetLocale, $domain);
            $this->assertNotNull($translatedContent);
            $this->assertArrayHasKey($key, $autoTranslatedResults);
        }
    }

    public function testCacheInvalidationWorkflow(): void
    {
        $key = 'cache.invalidation.test';
        $locale = 'fr';
        $domain = 'messages';
        $content = 'Test d\'invalidation du cache';

        // 1. Créer une traduction (elle sera mise en cache)
        $this->translationManager->setTranslation($key, $locale, $content, $domain);

        // 2. Vérifier que la traduction est en cache
        $cachedContent = $this->cacheManager->getCachedTranslation($key, $locale, $domain);
        $this->assertEquals($content, $cachedContent);

        // 3. Invalider le cache pour cette traduction
        $invalidated = $this->cacheManager->invalidateTranslation($key, $locale, $domain);
        $this->assertTrue($invalidated);

        // 4. Vérifier que la traduction n'est plus en cache
        $cachedContentAfterInvalidation = $this->cacheManager->getCachedTranslation($key, $locale, $domain);
        $this->assertNull($cachedContentAfterInvalidation);

        // 5. Vérifier que la traduction est toujours récupérable (depuis la DB)
        $retrievedContent = $this->translationManager->getTranslation($key, $locale, $domain);
        $this->assertEquals($content, $retrievedContent);

        // 6. Vérifier que la traduction est remise en cache après récupération
        $cachedContentAfterRetrieval = $this->cacheManager->getCachedTranslation($key, $locale, $domain);
        $this->assertEquals($content, $cachedContentAfterRetrieval);
    }

    public function testVersioningWorkflow(): void
    {
        $key = 'versioning.test';
        $locale = 'fr';
        $domain = 'messages';
        $initialContent = 'Version initiale';
        $updatedContent = 'Version mise à jour';
        $finalContent = 'Version finale';

        // 1. Créer la version initiale
        $this->translationManager->setTranslation($key, $locale, $initialContent, $domain);

        // 2. Première mise à jour
        $this->translationManager->setTranslation($key, $locale, $updatedContent, $domain);

        // 3. Deuxième mise à jour
        $this->translationManager->setTranslation($key, $locale, $finalContent, $domain);

        // 4. Vérifier qu'il y a 3 versions
        $versions = $this->versionManager->getAllVersions($key, $locale, $domain);
        $this->assertCount(3, $versions);

        // 5. Vérifier l'ordre et le contenu des versions
        $this->assertEquals('create', $versions[0]->getChangeType());
        $this->assertEquals($initialContent, $versions[0]->getContent());
        
        $this->assertEquals('update', $versions[1]->getChangeType());
        $this->assertEquals($updatedContent, $versions[1]->getContent());
        
        $this->assertEquals('update', $versions[2]->getChangeType());
        $this->assertEquals($finalContent, $versions[2]->getContent());

        // 6. Comparer deux versions
        $diff = $this->versionManager->compareVersions($versions[0]->getId(), $versions[2]->getId());
        $this->assertNotEmpty($diff);
        $this->assertStringContains($initialContent, $diff['old_content']);
        $this->assertStringContains($finalContent, $diff['new_content']);

        // 7. Restaurer une version précédente
        $restoredVersion = $this->versionManager->restoreVersion(
            $versions[1]->getId(),
            'Test de restauration vers version intermédiaire'
        );

        // 8. Vérifier que la restauration a créé une nouvelle version
        $versionsAfterRestore = $this->versionManager->getAllVersions($key, $locale, $domain);
        $this->assertCount(4, $versionsAfterRestore);
        $this->assertEquals('restore', $versionsAfterRestore[3]->getChangeType());

        // 9. Vérifier que le contenu actuel correspond à la version restaurée
        $currentContent = $this->translationManager->getTranslation($key, $locale, $domain);
        $this->assertEquals($updatedContent, $currentContent);
    }

    public function testKeyDetectionWorkflow(): void
    {
        $tempDir = sys_get_temp_dir() . '/dahovitech_integration_test_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            // 1. Créer des fichiers de test avec des clés de traduction
            $phpContent = '<?php
            class TestController {
                public function index() {
                    return $this->translator->trans("integration.detected.key1");
                }
            }';
            file_put_contents($tempDir . '/Controller.php', $phpContent);

            $twigContent = '<h1>{{ "integration.detected.key2"|trans }}</h1>';
            file_put_contents($tempDir . '/template.html.twig', $twigContent);

            // 2. Détecter les clés dans le projet
            $detectedKeys = $this->keyDetector->detectTranslationKeys($tempDir);
            $this->assertCount(2, $detectedKeys);

            $keys = array_column($detectedKeys, 'key');
            $this->assertContains('integration.detected.key1', $keys);
            $this->assertContains('integration.detected.key2', $keys);

            // 3. Créer une traduction existante
            $this->translationManager->setTranslation(
                'integration.detected.key1',
                'fr',
                'Clé détectée 1',
                'messages'
            );

            // 4. Générer un rapport de détection
            $existingKeys = [
                'integration.detected.key1' => true,
                'integration.orphan.key' => true
            ];

            $report = $this->keyDetector->generateDetectionReport($tempDir, $existingKeys);

            // 5. Vérifier le rapport
            $this->assertArrayHasKey('comparison', $report);
            $this->assertContains('integration.detected.key2', $report['comparison']['missing_keys']);
            $this->assertContains('integration.orphan.key', $report['comparison']['orphan_keys']);
            $this->assertEquals(50.0, $report['comparison']['coverage_percentage']);

        } finally {
            // Nettoyer les fichiers temporaires
            $this->removeDirectory($tempDir);
        }
    }

    public function testMultiLocaleWorkflow(): void
    {
        $key = 'multi.locale.test';
        $domain = 'messages';
        $locales = ['en', 'fr', 'es', 'de'];
        $contents = [
            'en' => 'Multi-locale test',
            'fr' => 'Test multi-locale',
            'es' => 'Prueba multi-idioma',
            'de' => 'Multi-Sprach-Test'
        ];

        // 1. Créer des traductions pour toutes les locales
        foreach ($locales as $locale) {
            $this->translationManager->setTranslation($key, $locale, $contents[$locale], $domain);
        }

        // 2. Vérifier que toutes les traductions sont récupérables
        foreach ($locales as $locale) {
            $retrievedContent = $this->translationManager->getTranslation($key, $locale, $domain);
            $this->assertEquals($contents[$locale], $retrievedContent);
        }

        // 3. Obtenir des suggestions de traduction pour une nouvelle locale
        $suggestions = $this->translationManager->suggestTranslations(
            $key,
            'en',
            ['it'],
            $domain,
            'mock'
        );

        $this->assertArrayHasKey('it', $suggestions);
        $this->assertTrue($suggestions['it']['success']);
        $this->assertNotEmpty($suggestions['it']['suggested_text']);

        // 4. Exporter toutes les traductions par locale
        foreach ($locales as $locale) {
            $exportedTranslations = $this->translationManager->exportTranslations($locale, $domain);
            $this->assertArrayHasKey($key, $exportedTranslations);
            $this->assertEquals($contents[$locale], $exportedTranslations[$key]);
        }

        // 5. Invalider le cache pour une locale spécifique
        $invalidated = $this->cacheManager->invalidateLocale('fr');
        $this->assertTrue($invalidated);

        // 6. Vérifier que les autres locales sont toujours en cache
        $cachedEn = $this->cacheManager->getCachedTranslation($key, 'en', $domain);
        $this->assertEquals($contents['en'], $cachedEn);

        // 7. Vérifier que la locale invalidée n'est plus en cache
        $cachedFr = $this->cacheManager->getCachedTranslation($key, 'fr', $domain);
        $this->assertNull($cachedFr);
    }

    public function testErrorHandlingWorkflow(): void
    {
        // 1. Tenter de récupérer une traduction inexistante
        $nonExistentTranslation = $this->translationManager->getTranslation(
            'non.existent.key',
            'fr',
            'messages'
        );
        $this->assertNull($nonExistentTranslation);

        // 2. Tenter de supprimer une traduction inexistante
        $deletionResult = $this->translationManager->removeTranslation(
            'non.existent.key',
            'fr',
            'messages'
        );
        $this->assertFalse($deletionResult);

        // 3. Tenter une traduction automatique avec une clé inexistante
        try {
            $this->translationManager->autoTranslate(
                'non.existent.key',
                'en',
                'fr',
                'messages',
                'mock'
            );
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContains('Traduction source non trouvée', $e->getMessage());
        }

        // 4. Tenter de restaurer une version inexistante
        try {
            $this->versionManager->restoreVersion(999999, 'Test de restauration impossible');
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $e) {
            $this->assertNotNull($e);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

