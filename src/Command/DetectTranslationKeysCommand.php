<?php

namespace Dahovitech\TranslatorBundle\Command;

use Dahovitech\TranslatorBundle\Service\TranslationKeyDetector;
use Dahovitech\TranslatorBundle\Service\TranslationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'dahovitech:translation:detect-keys',
    description: 'Détecte automatiquement les clés de traduction dans le code source'
)]
class DetectTranslationKeysCommand extends Command
{
    public function __construct(
        private TranslationKeyDetector $keyDetector,
        private TranslationManager $translationManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Chemin du projet à scanner', '.')
            ->addOption('locale', 'l', InputOption::VALUE_REQUIRED, 'Locale pour la comparaison', 'en')
            ->addOption('domain', 'd', InputOption::VALUE_REQUIRED, 'Domaine pour la comparaison', 'messages')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Fichier de sortie pour le rapport')
            ->addOption('format', 'f', InputOption::VALUE_REQUIRED, 'Format de sortie (json, csv, table)', 'table')
            ->addOption('missing-only', 'm', InputOption::VALUE_NONE, 'Afficher seulement les clés manquantes')
            ->addOption('orphan-only', null, InputOption::VALUE_NONE, 'Afficher seulement les clés orphelines')
            ->addOption('create-missing', 'c', InputOption::VALUE_NONE, 'Créer automatiquement les traductions manquantes')
            ->addOption('directories', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Répertoires à scanner', ['src', 'templates', 'assets'])
            ->addOption('extensions', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Extensions de fichiers à scanner', ['php', 'twig', 'js', 'yaml'])
            ->setHelp('
Cette commande scanne le code source pour détecter automatiquement les clés de traduction utilisées.
Elle peut comparer avec les traductions existantes pour identifier les clés manquantes ou orphelines.

Exemples d\'utilisation:
  # Scanner le projet actuel
  php bin/console dahovitech:translation:detect-keys

  # Scanner un répertoire spécifique
  php bin/console dahovitech:translation:detect-keys /path/to/project

  # Afficher seulement les clés manquantes
  php bin/console dahovitech:translation:detect-keys --missing-only

  # Créer automatiquement les traductions manquantes
  php bin/console dahovitech:translation:detect-keys --create-missing

  # Exporter le rapport en JSON
  php bin/console dahovitech:translation:detect-keys --format=json --output=report.json

  # Scanner seulement certains répertoires
  php bin/console dahovitech:translation:detect-keys --directories=src --directories=templates
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $projectPath = $input->getArgument('path');
        $locale = $input->getOption('locale');
        $domain = $input->getOption('domain');
        $outputFile = $input->getOption('output');
        $format = $input->getOption('format');
        $missingOnly = $input->getOption('missing-only');
        $orphanOnly = $input->getOption('orphan-only');
        $createMissing = $input->getOption('create-missing');
        $directories = $input->getOption('directories');
        $extensions = $input->getOption('extensions');

        // Valider le chemin du projet
        if (!is_dir($projectPath)) {
            $io->error("Le répertoire '{$projectPath}' n'existe pas.");
            return Command::FAILURE;
        }

        // Configurer le détecteur
        $this->keyDetector->setScanDirectories($directories);
        $this->keyDetector->setFileExtensions($extensions);

        $io->title('Détection des clés de traduction');
        $io->text("Scanning du projet: {$projectPath}");
        $io->text("Répertoires: " . implode(', ', $directories));
        $io->text("Extensions: " . implode(', ', $extensions));

        try {
            // Obtenir les clés existantes
            $existingKeys = $this->translationManager->getTranslationKeys();
            $existingTranslations = $this->translationManager->exportTranslations($locale, $domain);

            $io->progressStart();

            // Générer le rapport de détection
            $report = $this->keyDetector->generateDetectionReport($projectPath, array_keys($existingTranslations));

            $io->progressFinish();

            // Afficher les résultats selon les options
            if ($missingOnly) {
                $this->displayMissingKeys($io, $report);
            } elseif ($orphanOnly) {
                $this->displayOrphanKeys($io, $report);
            } else {
                $this->displayFullReport($io, $report, $format);
            }

            // Créer les traductions manquantes si demandé
            if ($createMissing && isset($report['comparison']['missing_keys'])) {
                $this->createMissingTranslations($io, $report['comparison']['missing_keys'], $locale, $domain);
            }

            // Sauvegarder le rapport si un fichier de sortie est spécifié
            if ($outputFile) {
                $this->saveReport($io, $report, $outputFile, $format);
            }

            $io->success('Détection terminée avec succès!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la détection: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayFullReport(SymfonyStyle $io, array $report, string $format): void
    {
        $io->section('Résumé de la détection');

        // Statistiques générales
        $stats = $report['statistics'];
        $io->definitionList(
            ['Clés détectées' => $stats['total_detected']],
            ['Clés uniques' => $stats['unique_keys']],
            ['Répartition par type de fichier' => '']
        );

        foreach ($stats['by_file_type'] as $fileType => $count) {
            $io->text("  - {$fileType}: {$count}");
        }

        $io->text('');
        $io->text('Répartition par domaine:');
        foreach ($stats['by_domain'] as $domain => $count) {
            $io->text("  - {$domain}: {$count}");
        }

        // Comparaison si disponible
        if (isset($report['comparison'])) {
            $comparison = $report['comparison'];
            $io->section('Comparaison avec les traductions existantes');
            
            $io->definitionList(
                ['Traductions existantes' => $comparison['existing_keys_count']],
                ['Clés manquantes' => $comparison['missing_keys_count']],
                ['Clés orphelines' => $comparison['orphan_keys_count']],
                ['Couverture' => $comparison['coverage_percentage'] . '%']
            );

            if ($format === 'table') {
                // Afficher les clés manquantes
                if (!empty($comparison['missing_keys'])) {
                    $io->section('Clés manquantes (échantillon)');
                    $this->displayKeysTable($io, array_slice($comparison['missing_keys'], 0, 10), 'Clé manquante');
                    
                    if (count($comparison['missing_keys']) > 10) {
                        $io->text('... et ' . (count($comparison['missing_keys']) - 10) . ' autres');
                    }
                }

                // Afficher les clés orphelines
                if (!empty($comparison['orphan_keys'])) {
                    $io->section('Clés orphelines (échantillon)');
                    $this->displayKeysTable($io, array_slice($comparison['orphan_keys'], 0, 10), 'Clé orpheline');
                    
                    if (count($comparison['orphan_keys']) > 10) {
                        $io->text('... et ' . (count($comparison['orphan_keys']) - 10) . ' autres');
                    }
                }
            }
        }
    }

    private function displayMissingKeys(SymfonyStyle $io, array $report): void
    {
        if (!isset($report['comparison']['missing_keys'])) {
            $io->warning('Aucune comparaison disponible. Utilisez --locale et --domain pour comparer.');
            return;
        }

        $missingKeys = $report['comparison']['missing_keys'];
        
        if (empty($missingKeys)) {
            $io->success('Aucune clé manquante trouvée!');
            return;
        }

        $io->section("Clés manquantes ({count($missingKeys)})");
        $this->displayKeysTable($io, $missingKeys, 'Clé manquante');
    }

    private function displayOrphanKeys(SymfonyStyle $io, array $report): void
    {
        if (!isset($report['comparison']['orphan_keys'])) {
            $io->warning('Aucune comparaison disponible. Utilisez --locale et --domain pour comparer.');
            return;
        }

        $orphanKeys = $report['comparison']['orphan_keys'];
        
        if (empty($orphanKeys)) {
            $io->success('Aucune clé orpheline trouvée!');
            return;
        }

        $io->section("Clés orphelines ({count($orphanKeys)})");
        $this->displayKeysTable($io, $orphanKeys, 'Clé orpheline');
    }

    private function displayKeysTable(SymfonyStyle $io, array $keys, string $header): void
    {
        $table = new Table($output = $io);
        $table->setHeaders([$header]);
        
        foreach ($keys as $key) {
            $table->addRow([$key]);
        }
        
        $table->render();
    }

    private function createMissingTranslations(SymfonyStyle $io, array $missingKeys, string $locale, string $domain): void
    {
        if (empty($missingKeys)) {
            return;
        }

        $io->section('Création des traductions manquantes');
        
        if (!$io->confirm("Créer {count($missingKeys)} traductions manquantes pour {$locale}/{$domain}?")) {
            return;
        }

        $created = 0;
        $progressBar = $io->createProgressBar(count($missingKeys));

        foreach ($missingKeys as $key) {
            try {
                // Créer une traduction vide ou avec la clé comme valeur par défaut
                $defaultContent = $io->choice(
                    "Contenu par défaut pour '{$key}':",
                    ['Laisser vide', 'Utiliser la clé', 'Saisir manuellement'],
                    'Utiliser la clé'
                );

                $content = match ($defaultContent) {
                    'Laisser vide' => '',
                    'Utiliser la clé' => $key,
                    'Saisir manuellement' => $io->ask("Contenu pour '{$key}':", $key)
                };

                $this->translationManager->setTranslation($key, $locale, $content, $domain);
                $created++;
                
            } catch (\Exception $e) {
                $io->error("Erreur lors de la création de '{$key}': " . $e->getMessage());
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->success("{$created} traductions créées avec succès!");
    }

    private function saveReport(SymfonyStyle $io, array $report, string $outputFile, string $format): void
    {
        try {
            $content = match ($format) {
                'json' => $this->keyDetector->exportReportAsJson($report),
                'csv' => $this->keyDetector->exportReportAsCsv($report),
                default => $this->keyDetector->exportReportAsJson($report)
            };

            file_put_contents($outputFile, $content);
            $io->text("Rapport sauvegardé dans: {$outputFile}");
            
        } catch (\Exception $e) {
            $io->error("Erreur lors de la sauvegarde: " . $e->getMessage());
        }
    }
}

