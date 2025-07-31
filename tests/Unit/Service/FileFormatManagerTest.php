<?php

namespace Dahovitech\TranslatorBundle\Tests\Unit\Service;

use Dahovitech\TranslatorBundle\Service\FileFormatManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FileFormatManagerTest extends TestCase
{
    private LoggerInterface $logger;
    private FileFormatManager $fileFormatManager;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fileFormatManager = new FileFormatManager($this->logger);
        $this->tempDir = sys_get_temp_dir() . '/dahovitech_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testDetectFormat(): void
    {
        $this->assertEquals('yaml', $this->fileFormatManager->detectFormat('test.yaml'));
        $this->assertEquals('yaml', $this->fileFormatManager->detectFormat('test.yml'));
        $this->assertEquals('json', $this->fileFormatManager->detectFormat('test.json'));
        $this->assertEquals('xliff', $this->fileFormatManager->detectFormat('test.xliff'));
        $this->assertEquals('xliff', $this->fileFormatManager->detectFormat('test.xlf'));
        $this->assertEquals('php', $this->fileFormatManager->detectFormat('test.php'));
    }

    public function testDetectFormatInvalidExtension(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->fileFormatManager->detectFormat('test.txt');
    }

    public function testGetSupportedFormats(): void
    {
        $formats = $this->fileFormatManager->getSupportedFormats();
        
        $this->assertIsArray($formats);
        $this->assertContains('yaml', $formats);
        $this->assertContains('json', $formats);
        $this->assertContains('xliff', $formats);
        $this->assertContains('php', $formats);
    }

    public function testImportFromYamlFile(): void
    {
        $yamlContent = "hello: Bonjour\ngoodbye: Au revoir\nnested:\n  key: Valeur imbriquée";
        $filePath = $this->tempDir . '/test.yaml';
        file_put_contents($filePath, $yamlContent);

        $result = $this->fileFormatManager->importFromFile($filePath);

        $this->assertIsArray($result);
        $this->assertEquals('Bonjour', $result['hello']);
        $this->assertEquals('Au revoir', $result['goodbye']);
        $this->assertEquals('Valeur imbriquée', $result['nested.key']);
    }

    public function testImportFromJsonFile(): void
    {
        $jsonContent = json_encode([
            'hello' => 'Bonjour',
            'goodbye' => 'Au revoir',
            'nested' => ['key' => 'Valeur imbriquée']
        ]);
        $filePath = $this->tempDir . '/test.json';
        file_put_contents($filePath, $jsonContent);

        $result = $this->fileFormatManager->importFromFile($filePath);

        $this->assertIsArray($result);
        $this->assertEquals('Bonjour', $result['hello']);
        $this->assertEquals('Au revoir', $result['goodbye']);
        $this->assertEquals('Valeur imbriquée', $result['nested.key']);
    }

    public function testImportFromPhpFile(): void
    {
        $phpContent = "<?php\nreturn [\n    'hello' => 'Bonjour',\n    'goodbye' => 'Au revoir'\n];";
        $filePath = $this->tempDir . '/test.php';
        file_put_contents($filePath, $phpContent);

        $result = $this->fileFormatManager->importFromFile($filePath);

        $this->assertIsArray($result);
        $this->assertEquals('Bonjour', $result['hello']);
        $this->assertEquals('Au revoir', $result['goodbye']);
    }

    public function testImportFromXliffFile(): void
    {
        $xliffContent = '<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="fr" datatype="plaintext">
        <body>
            <trans-unit id="hello">
                <source>hello</source>
                <target>Bonjour</target>
            </trans-unit>
            <trans-unit id="goodbye">
                <source>goodbye</source>
                <target>Au revoir</target>
            </trans-unit>
        </body>
    </file>
</xliff>';
        $filePath = $this->tempDir . '/test.xliff';
        file_put_contents($filePath, $xliffContent);

        $result = $this->fileFormatManager->importFromFile($filePath);

        $this->assertIsArray($result);
        $this->assertEquals('Bonjour', $result['hello']);
        $this->assertEquals('Au revoir', $result['goodbye']);
    }

    public function testExportToYamlFile(): void
    {
        $translations = [
            'hello' => 'Bonjour',
            'goodbye' => 'Au revoir',
            'nested.key' => 'Valeur imbriquée'
        ];
        $filePath = $this->tempDir . '/export.yaml';

        $result = $this->fileFormatManager->exportToFile($translations, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        $this->assertStringContains('hello: Bonjour', $content);
        $this->assertStringContains('goodbye: \'Au revoir\'', $content);
    }

    public function testExportToJsonFile(): void
    {
        $translations = [
            'hello' => 'Bonjour',
            'goodbye' => 'Au revoir'
        ];
        $filePath = $this->tempDir . '/export.json';

        $result = $this->fileFormatManager->exportToFile($translations, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        $this->assertEquals('Bonjour', $data['hello']);
        $this->assertEquals('Au revoir', $data['goodbye']);
    }

    public function testExportToPhpFile(): void
    {
        $translations = [
            'hello' => 'Bonjour',
            'goodbye' => 'Au revoir'
        ];
        $filePath = $this->tempDir . '/export.php';

        $result = $this->fileFormatManager->exportToFile($translations, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        
        $data = include $filePath;
        $this->assertEquals('Bonjour', $data['hello']);
        $this->assertEquals('Au revoir', $data['goodbye']);
    }

    public function testExportToXliffFile(): void
    {
        $translations = [
            'hello' => 'Bonjour',
            'goodbye' => 'Au revoir'
        ];
        $filePath = $this->tempDir . '/export.xliff';

        $result = $this->fileFormatManager->exportToFile($translations, $filePath);

        $this->assertTrue($result);
        $this->assertFileExists($filePath);
        
        $content = file_get_contents($filePath);
        $this->assertStringContains('<trans-unit id="hello">', $content);
        $this->assertStringContains('<target>Bonjour</target>', $content);
    }

    public function testValidateFile(): void
    {
        $yamlContent = "hello: Bonjour\ngoodbye: Au revoir";
        $filePath = $this->tempDir . '/valid.yaml';
        file_put_contents($filePath, $yamlContent);

        $errors = $this->fileFormatManager->validateFile($filePath);

        $this->assertEmpty($errors);
    }

    public function testValidateInvalidFile(): void
    {
        $invalidYamlContent = "hello: Bonjour\n  invalid: yaml: content";
        $filePath = $this->tempDir . '/invalid.yaml';
        file_put_contents($filePath, $invalidYamlContent);

        $errors = $this->fileFormatManager->validateFile($filePath);

        $this->assertNotEmpty($errors);
    }

    public function testValidateNonExistentFile(): void
    {
        $errors = $this->fileFormatManager->validateFile('/non/existent/file.yaml');

        $this->assertNotEmpty($errors);
        $this->assertContains("Le fichier n'existe pas", $errors);
    }

    public function testConvertFile(): void
    {
        $yamlContent = "hello: Bonjour\ngoodbye: Au revoir";
        $sourcePath = $this->tempDir . '/source.yaml';
        $targetPath = $this->tempDir . '/target.json';
        file_put_contents($sourcePath, $yamlContent);

        $result = $this->fileFormatManager->convertFile($sourcePath, $targetPath);

        $this->assertTrue($result);
        $this->assertFileExists($targetPath);
        
        $content = file_get_contents($targetPath);
        $data = json_decode($content, true);
        $this->assertEquals('Bonjour', $data['hello']);
        $this->assertEquals('Au revoir', $data['goodbye']);
    }

    public function testImportFromFileNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->fileFormatManager->importFromFile('/non/existent/file.yaml');
    }

    public function testImportFromFileUnsupportedFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->fileFormatManager->importFromFile('test.txt', 'unsupported');
    }

    public function testExportToFileUnsupportedFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->fileFormatManager->exportToFile([], 'test.txt', 'unsupported');
    }

    public function testImportFromInvalidJsonFile(): void
    {
        $invalidJsonContent = '{"hello": "Bonjour", "invalid": json}';
        $filePath = $this->tempDir . '/invalid.json';
        file_put_contents($filePath, $invalidJsonContent);

        $this->expectException(\RuntimeException::class);
        $this->fileFormatManager->importFromFile($filePath);
    }

    public function testImportFromInvalidXliffFile(): void
    {
        $invalidXliffContent = '<?xml version="1.0"?><invalid>xml</invalid>';
        $filePath = $this->tempDir . '/invalid.xliff';
        file_put_contents($filePath, $invalidXliffContent);

        $this->expectException(\RuntimeException::class);
        $this->fileFormatManager->importFromFile($filePath);
    }

    public function testImportFromPhpFileNotReturningArray(): void
    {
        $phpContent = "<?php\necho 'Not an array';";
        $filePath = $this->tempDir . '/invalid.php';
        file_put_contents($filePath, $phpContent);

        $this->expectException(\RuntimeException::class);
        $this->fileFormatManager->importFromFile($filePath);
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

