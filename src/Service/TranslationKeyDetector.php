<?php

namespace Dahovitech\TranslatorBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class TranslationKeyDetector
{
    private array $patterns = [
        // Patterns pour PHP
        'php' => [
            // $translator->trans('key')
            '/\$\w+->trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // $this->translator->trans('key')
            '/\$this->\w+->trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // trans('key')
            '/trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // t('key') - raccourci commun
            '/\bt\([\'"]([^\'"\)]+)[\'"]\)/',
            // TranslatorInterface::trans('key')
            '/TranslatorInterface::trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // $translator->trans('key', [], 'domain')
            '/\$\w+->trans\([\'"]([^\'"\)]+)[\'"],\s*\[\],\s*[\'"]([^\'"\)]+)[\'"]\)/',
        ],
        
        // Patterns pour Twig
        'twig' => [
            // {{ 'key'|trans }}
            '/[\'"]([^\'"\|]+)[\'"]\s*\|\s*trans/',
            // {% trans %}key{% endtrans %}
            '/{%\s*trans\s*%}([^{%]+){%\s*endtrans\s*%}/',
            // {{ trans('key') }}
            '/trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // {{ 'key'|trans({}, 'domain') }}
            '/[\'"]([^\'"\|]+)[\'"]\s*\|\s*trans\([^,]*,\s*[\'"]([^\'"\)]+)[\'"]\)/',
        ],
        
        // Patterns pour JavaScript
        'js' => [
            // Translator.trans('key')
            '/Translator\.trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // trans('key')
            '/trans\([\'"]([^\'"\)]+)[\'"]\)/',
            // i18n.t('key')
            '/i18n\.t\([\'"]([^\'"\)]+)[\'"]\)/',
            // __('key')
            '/__\([\'"]([^\'"\)]+)[\'"]\)/',
        ],
        
        // Patterns pour YAML (dans les fichiers de configuration)
        'yaml' => [
            // label: 'key'
            '/label:\s*[\'"]([^\'"\n]+)[\'"]/',
            // title: 'key'
            '/title:\s*[\'"]([^\'"\n]+)[\'"]/',
            // message: 'key'
            '/message:\s*[\'"]([^\'"\n]+)[\'"]/',
        ]
    ];

    private array $excludePatterns = [
        // Exclure les variables et expressions dynamiques
        '/\$\w+/',
        '/\{\{.*\}\}/',
        '/\{%.*%\}/',
        // Exclure les URLs et chemins
        '/^https?:\/\//',
        '/^\/[a-zA-Z0-9_\-\/]+$/',
        // Exclure les valeurs numériques et booléennes
        '/^\d+$/',
        '/^(true|false|null)$/i',
        // Exclure les expressions trop courtes ou trop longues
        '/^.{1,2}$/',
        '/^.{100,}$/',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private array $scanDirectories = ['src', 'templates', 'assets'],
        private array $fileExtensions = ['php', 'twig', 'js', 'ts', 'yaml', 'yml']
    ) {
    }

    /**
     * Détecte toutes les clés de traduction dans un projet
     */
    public function detectTranslationKeys(string $projectPath): array
    {
        $this->logger->info('Starting translation key detection', ['path' => $projectPath]);

        $detectedKeys = [];
        $finder = new Finder();

        try {
            $finder->files()
                ->in($this->getValidDirectories($projectPath))
                ->name($this->getFileNamePatterns());

            foreach ($finder as $file) {
                $fileKeys = $this->extractKeysFromFile($file);
                $detectedKeys = array_merge($detectedKeys, $fileKeys);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error during key detection', [
                'error' => $e->getMessage(),
                'path' => $projectPath
            ]);
            throw $e;
        }

        // Nettoyer et dédupliquer les clés
        $cleanedKeys = $this->cleanAndDeduplicateKeys($detectedKeys);

        $this->logger->info('Translation key detection completed', [
            'total_keys_found' => count($cleanedKeys),
            'files_scanned' => iterator_count($finder)
        ]);

        return $cleanedKeys;
    }

    /**
     * Détecte les clés dans un fichier spécifique
     */
    public function detectKeysInFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $file = new SplFileInfo($filePath, dirname($filePath), $filePath);
        return $this->extractKeysFromFile($file);
    }

    /**
     * Détecte les clés dans un contenu de fichier
     */
    public function detectKeysInContent(string $content, string $fileType = 'php'): array
    {
        $patterns = $this->patterns[$fileType] ?? $this->patterns['php'];
        $keys = [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $key) {
                    if (!$this->shouldExcludeKey($key)) {
                        $keys[] = [
                            'key' => $key,
                            'domain' => $matches[2][0] ?? 'messages',
                            'pattern' => $pattern,
                            'file_type' => $fileType
                        ];
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Compare les clés détectées avec les traductions existantes
     */
    public function findMissingKeys(array $detectedKeys, array $existingKeys): array
    {
        $detectedKeyNames = array_unique(array_column($detectedKeys, 'key'));
        $missingKeys = array_diff($detectedKeyNames, $existingKeys);

        return array_values($missingKeys);
    }

    /**
     * Trouve les clés orphelines (présentes en base mais non utilisées)
     */
    public function findOrphanKeys(array $detectedKeys, array $existingKeys): array
    {
        $detectedKeyNames = array_unique(array_column($detectedKeys, 'key'));
        $orphanKeys = array_diff($existingKeys, $detectedKeyNames);

        return array_values($orphanKeys);
    }

    /**
     * Génère un rapport de détection
     */
    public function generateDetectionReport(string $projectPath, array $existingKeys = []): array
    {
        $detectedKeys = $this->detectTranslationKeys($projectPath);
        
        $report = [
            'scan_info' => [
                'project_path' => $projectPath,
                'scan_date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'directories_scanned' => $this->scanDirectories,
                'file_extensions' => $this->fileExtensions
            ],
            'detected_keys' => $detectedKeys,
            'statistics' => [
                'total_detected' => count($detectedKeys),
                'unique_keys' => count(array_unique(array_column($detectedKeys, 'key'))),
                'by_file_type' => $this->getStatsByFileType($detectedKeys),
                'by_domain' => $this->getStatsByDomain($detectedKeys)
            ]
        ];

        if (!empty($existingKeys)) {
            $missingKeys = $this->findMissingKeys($detectedKeys, $existingKeys);
            $orphanKeys = $this->findOrphanKeys($detectedKeys, $existingKeys);

            $report['comparison'] = [
                'existing_keys_count' => count($existingKeys),
                'missing_keys' => $missingKeys,
                'missing_keys_count' => count($missingKeys),
                'orphan_keys' => $orphanKeys,
                'orphan_keys_count' => count($orphanKeys),
                'coverage_percentage' => $this->calculateCoverage($detectedKeys, $existingKeys)
            ];
        }

        return $report;
    }

    /**
     * Exporte le rapport au format JSON
     */
    public function exportReportAsJson(array $report): string
    {
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Exporte le rapport au format CSV
     */
    public function exportReportAsCsv(array $report): string
    {
        $csv = "Key,Domain,File Type,Pattern,File Path\n";
        
        foreach ($report['detected_keys'] as $keyInfo) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                str_replace('"', '""', $keyInfo['key']),
                $keyInfo['domain'],
                $keyInfo['file_type'],
                str_replace('"', '""', $keyInfo['pattern']),
                $keyInfo['file_path'] ?? ''
            );
        }

        return $csv;
    }

    /**
     * Configure les répertoires à scanner
     */
    public function setScanDirectories(array $directories): void
    {
        $this->scanDirectories = $directories;
    }

    /**
     * Configure les extensions de fichiers à scanner
     */
    public function setFileExtensions(array $extensions): void
    {
        $this->fileExtensions = $extensions;
    }

    /**
     * Ajoute un pattern personnalisé pour un type de fichier
     */
    public function addPattern(string $fileType, string $pattern): void
    {
        if (!isset($this->patterns[$fileType])) {
            $this->patterns[$fileType] = [];
        }
        $this->patterns[$fileType][] = $pattern;
    }

    /**
     * Ajoute un pattern d'exclusion
     */
    public function addExcludePattern(string $pattern): void
    {
        $this->excludePatterns[] = $pattern;
    }

    /**
     * Extrait les clés d'un fichier
     */
    private function extractKeysFromFile(SplFileInfo $file): array
    {
        $extension = $file->getExtension();
        $content = $file->getContents();
        $keys = [];

        // Déterminer le type de fichier
        $fileType = $this->getFileType($extension);
        
        if (!isset($this->patterns[$fileType])) {
            return $keys;
        }

        $detectedKeys = $this->detectKeysInContent($content, $fileType);
        
        // Ajouter les informations du fichier
        foreach ($detectedKeys as $keyInfo) {
            $keyInfo['file_path'] = $file->getRelativePathname();
            $keyInfo['file_size'] = $file->getSize();
            $keys[] = $keyInfo;
        }

        return $keys;
    }

    /**
     * Détermine le type de fichier basé sur l'extension
     */
    private function getFileType(string $extension): string
    {
        return match (strtolower($extension)) {
            'php' => 'php',
            'twig' => 'twig',
            'js', 'ts' => 'js',
            'yaml', 'yml' => 'yaml',
            default => 'php'
        };
    }

    /**
     * Vérifie si une clé doit être exclue
     */
    private function shouldExcludeKey(string $key): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Nettoie et déduplique les clés
     */
    private function cleanAndDeduplicateKeys(array $keys): array
    {
        $cleaned = [];
        $seen = [];

        foreach ($keys as $keyInfo) {
            $key = trim($keyInfo['key']);
            $domain = $keyInfo['domain'] ?? 'messages';
            $identifier = $key . '|' . $domain;

            if (!isset($seen[$identifier]) && !empty($key)) {
                $seen[$identifier] = true;
                $keyInfo['key'] = $key;
                $cleaned[] = $keyInfo;
            }
        }

        return $cleaned;
    }

    /**
     * Obtient les répertoires valides à scanner
     */
    private function getValidDirectories(string $projectPath): array
    {
        $validDirs = [];
        
        foreach ($this->scanDirectories as $dir) {
            $fullPath = $projectPath . DIRECTORY_SEPARATOR . $dir;
            if (is_dir($fullPath)) {
                $validDirs[] = $fullPath;
            }
        }

        if (empty($validDirs)) {
            $validDirs[] = $projectPath; // Fallback au répertoire racine
        }

        return $validDirs;
    }

    /**
     * Génère les patterns de noms de fichiers
     */
    private function getFileNamePatterns(): array
    {
        return array_map(fn($ext) => "*.{$ext}", $this->fileExtensions);
    }

    /**
     * Calcule les statistiques par type de fichier
     */
    private function getStatsByFileType(array $keys): array
    {
        $stats = [];
        foreach ($keys as $keyInfo) {
            $fileType = $keyInfo['file_type'];
            $stats[$fileType] = ($stats[$fileType] ?? 0) + 1;
        }
        return $stats;
    }

    /**
     * Calcule les statistiques par domaine
     */
    private function getStatsByDomain(array $keys): array
    {
        $stats = [];
        foreach ($keys as $keyInfo) {
            $domain = $keyInfo['domain'];
            $stats[$domain] = ($stats[$domain] ?? 0) + 1;
        }
        return $stats;
    }

    /**
     * Calcule le pourcentage de couverture
     */
    private function calculateCoverage(array $detectedKeys, array $existingKeys): float
    {
        $detectedKeyNames = array_unique(array_column($detectedKeys, 'key'));
        $totalDetected = count($detectedKeyNames);
        
        if ($totalDetected === 0) {
            return 100.0;
        }

        $covered = count(array_intersect($detectedKeyNames, $existingKeys));
        return round(($covered / $totalDetected) * 100, 2);
    }
}

