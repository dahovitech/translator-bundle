<?php

namespace Dahovitech\TranslatorBundle\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Psr\Log\LoggerInterface;

class FileFormatManager
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Importe des traductions depuis un fichier
     */
    public function importFromFile(string $filePath, string $format = null): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("Le fichier {$filePath} n'existe pas.");
        }

        if ($format === null) {
            $format = $this->detectFormat($filePath);
        }

        $this->logger->info('Importing translations from file', [
            'file' => $filePath,
            'format' => $format
        ]);

        return match ($format) {
            'yaml', 'yml' => $this->importFromYaml($filePath),
            'json' => $this->importFromJson($filePath),
            'xliff', 'xlf' => $this->importFromXliff($filePath),
            'php' => $this->importFromPhp($filePath),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}")
        };
    }

    /**
     * Exporte des traductions vers un fichier
     */
    public function exportToFile(array $translations, string $filePath, string $format = null): bool
    {
        if ($format === null) {
            $format = $this->detectFormat($filePath);
        }

        $this->logger->info('Exporting translations to file', [
            'file' => $filePath,
            'format' => $format,
            'count' => count($translations)
        ]);

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return match ($format) {
            'yaml', 'yml' => $this->exportToYaml($translations, $filePath),
            'json' => $this->exportToJson($translations, $filePath),
            'xliff', 'xlf' => $this->exportToXliff($translations, $filePath),
            'php' => $this->exportToPhp($translations, $filePath),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}")
        };
    }

    /**
     * Détecte le format d'un fichier basé sur son extension
     */
    public function detectFormat(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return match ($extension) {
            'yaml', 'yml' => 'yaml',
            'json' => 'json',
            'xliff', 'xlf' => 'xliff',
            'php' => 'php',
            default => throw new \InvalidArgumentException("Extension de fichier non reconnue: {$extension}")
        };
    }

    /**
     * Obtient les formats supportés
     */
    public function getSupportedFormats(): array
    {
        return ['yaml', 'yml', 'json', 'xliff', 'xlf', 'php'];
    }

    /**
     * Importe depuis un fichier YAML
     */
    private function importFromYaml(string $filePath): array
    {
        try {
            $content = file_get_contents($filePath);
            $data = Yaml::parse($content);
            
            return $this->flattenArray($data);
        } catch (ParseException $e) {
            $this->logger->error('Failed to parse YAML file', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Erreur lors du parsing du fichier YAML: " . $e->getMessage());
        }
    }

    /**
     * Importe depuis un fichier JSON
     */
    private function importFromJson(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = json_last_error_msg();
            $this->logger->error('Failed to parse JSON file', [
                'file' => $filePath,
                'error' => $error
            ]);
            throw new \RuntimeException("Erreur lors du parsing du fichier JSON: {$error}");
        }
        
        return $this->flattenArray($data);
    }

    /**
     * Importe depuis un fichier XLIFF
     */
    private function importFromXliff(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        try {
            $xml = new \SimpleXMLElement($content);
            $translations = [];
            
            // Support XLIFF 1.2 et 2.0
            if (isset($xml->file)) {
                // XLIFF 1.2
                foreach ($xml->file->body->{'trans-unit'} as $transUnit) {
                    $id = (string) $transUnit['id'];
                    $target = (string) $transUnit->target;
                    if (!empty($target)) {
                        $translations[$id] = $target;
                    }
                }
            } elseif (isset($xml->unit)) {
                // XLIFF 2.0
                foreach ($xml->unit as $unit) {
                    $id = (string) $unit['id'];
                    $target = (string) $unit->segment->target;
                    if (!empty($target)) {
                        $translations[$id] = $target;
                    }
                }
            }
            
            return $translations;
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse XLIFF file', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException("Erreur lors du parsing du fichier XLIFF: " . $e->getMessage());
        }
    }

    /**
     * Importe depuis un fichier PHP
     */
    private function importFromPhp(string $filePath): array
    {
        $data = include $filePath;
        
        if (!is_array($data)) {
            throw new \RuntimeException("Le fichier PHP doit retourner un tableau");
        }
        
        return $this->flattenArray($data);
    }

    /**
     * Exporte vers un fichier YAML
     */
    private function exportToYaml(array $translations, string $filePath): bool
    {
        try {
            $nestedArray = $this->unflattenArray($translations);
            $yamlContent = Yaml::dump($nestedArray, 4, 2);
            
            return file_put_contents($filePath, $yamlContent) !== false;
        } catch (\Exception $e) {
            $this->logger->error('Failed to export to YAML', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Exporte vers un fichier JSON
     */
    private function exportToJson(array $translations, string $filePath): bool
    {
        try {
            $nestedArray = $this->unflattenArray($translations);
            $jsonContent = json_encode($nestedArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            return file_put_contents($filePath, $jsonContent) !== false;
        } catch (\Exception $e) {
            $this->logger->error('Failed to export to JSON', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Exporte vers un fichier XLIFF
     */
    private function exportToXliff(array $translations, string $filePath, string $sourceLocale = 'en', string $targetLocale = 'fr'): bool
    {
        try {
            $xml = new \DOMDocument('1.0', 'UTF-8');
            $xml->formatOutput = true;
            
            // Créer la structure XLIFF 1.2
            $xliff = $xml->createElement('xliff');
            $xliff->setAttribute('version', '1.2');
            $xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:1.2');
            $xml->appendChild($xliff);
            
            $file = $xml->createElement('file');
            $file->setAttribute('source-language', $sourceLocale);
            $file->setAttribute('target-language', $targetLocale);
            $file->setAttribute('datatype', 'plaintext');
            $xliff->appendChild($file);
            
            $body = $xml->createElement('body');
            $file->appendChild($body);
            
            foreach ($translations as $key => $value) {
                $transUnit = $xml->createElement('trans-unit');
                $transUnit->setAttribute('id', $key);
                
                $source = $xml->createElement('source', htmlspecialchars($key));
                $target = $xml->createElement('target', htmlspecialchars($value));
                
                $transUnit->appendChild($source);
                $transUnit->appendChild($target);
                $body->appendChild($transUnit);
            }
            
            return $xml->save($filePath) !== false;
        } catch (\Exception $e) {
            $this->logger->error('Failed to export to XLIFF', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Exporte vers un fichier PHP
     */
    private function exportToPhp(array $translations, string $filePath): bool
    {
        try {
            $nestedArray = $this->unflattenArray($translations);
            $phpContent = "<?php\n\nreturn " . var_export($nestedArray, true) . ";\n";
            
            return file_put_contents($filePath, $phpContent) !== false;
        } catch (\Exception $e) {
            $this->logger->error('Failed to export to PHP', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Aplatit un tableau multidimensionnel en utilisant la notation par points
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Reconstitue un tableau multidimensionnel depuis un tableau aplati
     */
    private function unflattenArray(array $array): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $keys = explode('.', $key);
            $current = &$result;
            
            foreach ($keys as $k) {
                if (!isset($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
            
            $current = $value;
        }
        
        return $result;
    }

    /**
     * Valide le contenu d'un fichier de traduction
     */
    public function validateFile(string $filePath, string $format = null): array
    {
        $errors = [];
        
        if (!file_exists($filePath)) {
            $errors[] = "Le fichier n'existe pas";
            return $errors;
        }
        
        if (!is_readable($filePath)) {
            $errors[] = "Le fichier n'est pas lisible";
            return $errors;
        }
        
        if ($format === null) {
            try {
                $format = $this->detectFormat($filePath);
            } catch (\Exception $e) {
                $errors[] = "Format de fichier non reconnu";
                return $errors;
            }
        }
        
        try {
            $this->importFromFile($filePath, $format);
        } catch (\Exception $e) {
            $errors[] = "Erreur lors du parsing: " . $e->getMessage();
        }
        
        return $errors;
    }

    /**
     * Convertit un fichier d'un format vers un autre
     */
    public function convertFile(string $sourcePath, string $targetPath, string $sourceFormat = null, string $targetFormat = null): bool
    {
        try {
            $translations = $this->importFromFile($sourcePath, $sourceFormat);
            return $this->exportToFile($translations, $targetPath, $targetFormat);
        } catch (\Exception $e) {
            $this->logger->error('Failed to convert file', [
                'source' => $sourcePath,
                'target' => $targetPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

