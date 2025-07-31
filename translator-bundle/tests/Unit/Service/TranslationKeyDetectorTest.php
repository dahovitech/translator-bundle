<?php

namespace Dahovitech\TranslatorBundle\Tests\Unit\Service;

use Dahovitech\TranslatorBundle\Service\TranslationKeyDetector;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class TranslationKeyDetectorTest extends TestCase
{
    private LoggerInterface $logger;
    private TranslationKeyDetector $keyDetector;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->keyDetector = new TranslationKeyDetector($this->logger);
        $this->tempDir = sys_get_temp_dir() . '/dahovitech_detector_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testDetectKeysInPhpFile(): void
    {
        $phpContent = '<?php
class TestController
{
    public function index()
    {
        $translator->trans("user.welcome");
        $this->translator->trans("user.profile.name");
        trans("button.save");
        t("quick.translation");
        return $translator->trans("page.title", [], "admin");
    }
}';
        $filePath = $this->tempDir . '/TestController.php';
        file_put_contents($filePath, $phpContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertCount(5, $detectedKeys);
        $this->assertContains('user.welcome', array_column($detectedKeys, 'key'));
        $this->assertContains('user.profile.name', array_column($detectedKeys, 'key'));
        $this->assertContains('button.save', array_column($detectedKeys, 'key'));
        $this->assertContains('quick.translation', array_column($detectedKeys, 'key'));
        $this->assertContains('page.title', array_column($detectedKeys, 'key'));
    }

    public function testDetectKeysInTwigFile(): void
    {
        $twigContent = '
<h1>{{ "page.title"|trans }}</h1>
<p>{% trans %}user.welcome.message{% endtrans %}</p>
<button>{{ trans("button.submit") }}</button>
<span>{{ "form.label.name"|trans({}, "forms") }}</span>
';
        $filePath = $this->tempDir . '/template.html.twig';
        file_put_contents($filePath, $twigContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertCount(4, $detectedKeys);
        $this->assertContains('page.title', array_column($detectedKeys, 'key'));
        $this->assertContains('user.welcome.message', array_column($detectedKeys, 'key'));
        $this->assertContains('button.submit', array_column($detectedKeys, 'key'));
        $this->assertContains('form.label.name', array_column($detectedKeys, 'key'));
    }

    public function testDetectKeysInJavaScriptFile(): void
    {
        $jsContent = '
function showMessage() {
    alert(Translator.trans("js.alert.message"));
    console.log(trans("debug.info"));
    const title = i18n.t("modal.title");
    return __("common.loading");
}
';
        $filePath = $this->tempDir . '/script.js';
        file_put_contents($filePath, $jsContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertCount(4, $detectedKeys);
        $this->assertContains('js.alert.message', array_column($detectedKeys, 'key'));
        $this->assertContains('debug.info', array_column($detectedKeys, 'key'));
        $this->assertContains('modal.title', array_column($detectedKeys, 'key'));
        $this->assertContains('common.loading', array_column($detectedKeys, 'key'));
    }

    public function testDetectKeysInYamlFile(): void
    {
        $yamlContent = '
routes:
    home:
        path: /
        defaults:
            _controller: App\Controller\HomeController::index
            page_title: "home.title"
    user_profile:
        path: /profile
        defaults:
            breadcrumb: "user.profile.breadcrumb"
';
        $filePath = $this->tempDir . '/routes.yaml';
        file_put_contents($filePath, $yamlContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertCount(2, $detectedKeys);
        $this->assertContains('home.title', array_column($detectedKeys, 'key'));
        $this->assertContains('user.profile.breadcrumb', array_column($detectedKeys, 'key'));
    }

    public function testDetectTranslationKeysInProject(): void
    {
        // Créer une structure de projet de test
        mkdir($this->tempDir . '/src', 0777, true);
        mkdir($this->tempDir . '/templates', 0777, true);

        // Fichier PHP
        file_put_contents($this->tempDir . '/src/Controller.php', '<?php
        class Controller {
            public function action() {
                return $this->translator->trans("controller.action");
            }
        }');

        // Fichier Twig
        file_put_contents($this->tempDir . '/templates/page.html.twig', '
        <h1>{{ "template.title"|trans }}</h1>
        ');

        $detectedKeys = $this->keyDetector->detectTranslationKeys($this->tempDir);

        $this->assertCount(2, $detectedKeys);
        $keys = array_column($detectedKeys, 'key');
        $this->assertContains('controller.action', $keys);
        $this->assertContains('template.title', $keys);
    }

    public function testGenerateDetectionReport(): void
    {
        // Créer des fichiers de test
        file_put_contents($this->tempDir . '/test.php', '<?php
        $translator->trans("new.key");
        $translator->trans("existing.key");
        ');

        $existingKeys = [
            'existing.key' => true,
            'orphan.key' => true
        ];

        $report = $this->keyDetector->generateDetectionReport($this->tempDir, $existingKeys);

        $this->assertArrayHasKey('scan_info', $report);
        $this->assertArrayHasKey('detected_keys', $report);
        $this->assertArrayHasKey('statistics', $report);
        $this->assertArrayHasKey('comparison', $report);

        $this->assertEquals($this->tempDir, $report['scan_info']['project_path']);
        $this->assertCount(2, $report['detected_keys']);
        $this->assertEquals(2, $report['statistics']['total_detected']);
        $this->assertEquals(2, $report['statistics']['unique_keys']);
        $this->assertContains('new.key', $report['comparison']['missing_keys']);
        $this->assertContains('orphan.key', $report['comparison']['orphan_keys']);
    }

    public function testCustomPatterns(): void
    {
        $customPatterns = [
            'php' => ['/customTrans\([\'"]([^\'"\)]+)[\'"]\)/']
        ];

        $this->keyDetector->setCustomPatterns($customPatterns);

        $phpContent = '<?php
        customTrans("custom.pattern.key");
        $translator->trans("standard.key");
        ';
        $filePath = $this->tempDir . '/custom.php';
        file_put_contents($filePath, $phpContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $keys = array_column($detectedKeys, 'key');
        $this->assertContains('custom.pattern.key', $keys);
        $this->assertContains('standard.key', $keys);
    }

    public function testExcludePatterns(): void
    {
        $excludePatterns = ['/^variable\./'];
        $this->keyDetector->setExcludePatterns($excludePatterns);

        $phpContent = '<?php
        $translator->trans("variable.name");
        $translator->trans("valid.key");
        ';
        $filePath = $this->tempDir . '/exclude.php';
        file_put_contents($filePath, $phpContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $keys = array_column($detectedKeys, 'key');
        $this->assertNotContains('variable.name', $keys);
        $this->assertContains('valid.key', $keys);
    }

    public function testDetectKeysWithDomainExtraction(): void
    {
        $phpContent = '<?php
        $translator->trans("user.name", [], "forms");
        $translator->trans("error.message", [], "validators");
        $translator->trans("button.save"); // domain par défaut
        ';
        $filePath = $this->tempDir . '/domains.php';
        file_put_contents($filePath, $phpContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertCount(3, $detectedKeys);
        
        // Vérifier les domaines détectés
        $keyWithForms = array_filter($detectedKeys, fn($k) => $k['key'] === 'user.name');
        $this->assertEquals('forms', reset($keyWithForms)['domain']);
        
        $keyWithValidators = array_filter($detectedKeys, fn($k) => $k['key'] === 'error.message');
        $this->assertEquals('validators', reset($keyWithValidators)['domain']);
        
        $keyWithDefault = array_filter($detectedKeys, fn($k) => $k['key'] === 'button.save');
        $this->assertEquals('messages', reset($keyWithDefault)['domain']);
    }

    public function testDetectKeysInNonExistentFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File does not exist");
        
        $this->keyDetector->detectKeysInFile('/non/existent/file.php');
    }

    public function testDetectKeysInUnsupportedFileType(): void
    {
        $filePath = $this->tempDir . '/unsupported.txt';
        file_put_contents($filePath, 'Some text content');

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertEmpty($detectedKeys);
    }

    public function testGetSupportedFileTypes(): void
    {
        $supportedTypes = $this->keyDetector->getSupportedFileTypes();

        $this->assertIsArray($supportedTypes);
        $this->assertContains('php', $supportedTypes);
        $this->assertContains('twig', $supportedTypes);
        $this->assertContains('js', $supportedTypes);
        $this->assertContains('yaml', $supportedTypes);
    }

    public function testDetectKeysWithLineNumbers(): void
    {
        $phpContent = '<?php
// Line 2
class Test {
    public function method() {
        // Line 5
        $translator->trans("line.five.key");
        
        // Line 8
        return trans("line.eight.key");
    }
}';
        $filePath = $this->tempDir . '/lines.php';
        file_put_contents($filePath, $phpContent);

        $detectedKeys = $this->keyDetector->detectKeysInFile($filePath);

        $this->assertCount(2, $detectedKeys);
        
        $lineFiveKey = array_filter($detectedKeys, fn($k) => $k['key'] === 'line.five.key');
        $this->assertEquals(6, reset($lineFiveKey)['line_number']);
        
        $lineEightKey = array_filter($detectedKeys, fn($k) => $k['key'] === 'line.eight.key');
        $this->assertEquals(9, reset($lineEightKey)['line_number']);
    }

    public function testDetectKeysWithFileExtensionFilter(): void
    {
        // Créer des fichiers avec différentes extensions
        file_put_contents($this->tempDir . '/test.php', '<?php trans("php.key");');
        file_put_contents($this->tempDir . '/test.js', 'trans("js.key");');
        file_put_contents($this->tempDir . '/test.txt', 'trans("txt.key");');

        // Détecter seulement les fichiers PHP
        $detectedKeys = $this->keyDetector->detectTranslationKeys($this->tempDir, ['php']);

        $keys = array_column($detectedKeys, 'key');
        $this->assertContains('php.key', $keys);
        $this->assertNotContains('js.key', $keys);
        $this->assertNotContains('txt.key', $keys);
    }

    public function testDetectKeysWithDirectoryExclusion(): void
    {
        // Créer une structure avec répertoires à exclure
        mkdir($this->tempDir . '/src', 0777, true);
        mkdir($this->tempDir . '/vendor', 0777, true);
        mkdir($this->tempDir . '/var', 0777, true);

        file_put_contents($this->tempDir . '/src/valid.php', '<?php trans("valid.key");');
        file_put_contents($this->tempDir . '/vendor/excluded.php', '<?php trans("vendor.key");');
        file_put_contents($this->tempDir . '/var/excluded.php', '<?php trans("var.key");');

        $excludePatterns = ['/vendor/', '/var/'];
        $this->keyDetector->setExcludePatterns($excludePatterns);

        $detectedKeys = $this->keyDetector->detectTranslationKeys($this->tempDir);

        $keys = array_column($detectedKeys, 'key');
        $this->assertContains('valid.key', $keys);
        $this->assertNotContains('vendor.key', $keys);
        $this->assertNotContains('var.key', $keys);
    }

    public function testGenerateReportWithStatistics(): void
    {
        // Créer des fichiers de différents types
        file_put_contents($this->tempDir . '/controller.php', '<?php
        trans("php.key1");
        trans("php.key2");
        ');
        
        file_put_contents($this->tempDir . '/template.twig', '
        {{ "twig.key1"|trans }}
        {{ "twig.key2"|trans }}
        {{ "twig.key3"|trans }}
        ');

        $existingKeys = ['php.key1' => true];

        $report = $this->keyDetector->generateDetectionReport($this->tempDir, $existingKeys);

        $stats = $report['statistics'];
        $this->assertEquals(5, $stats['total_detected']);
        $this->assertEquals(5, $stats['unique_keys']);
        $this->assertEquals(2, $stats['by_file_type']['php']);
        $this->assertEquals(3, $stats['by_file_type']['twig']);

        $comparison = $report['comparison'];
        $this->assertEquals(1, $comparison['existing_keys_count']);
        $this->assertCount(4, $comparison['missing_keys']);
        $this->assertEquals(20.0, $comparison['coverage_percentage']);
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

