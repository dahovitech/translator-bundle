<?php

namespace Dahovitech\TranslatorBundle\Service;

use Dahovitech\TranslatorBundle\Entity\Translation;
use Dahovitech\TranslatorBundle\Entity\TranslationVersion;
use Dahovitech\TranslatorBundle\Repository\TranslationVersionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class VersionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslationVersionRepository $versionRepository,
        private LoggerInterface $logger,
        private ?Security $security = null,
        private int $maxVersionsPerTranslation = 50
    ) {
    }

    /**
     * Crée une nouvelle version pour une traduction
     */
    public function createVersion(
        Translation $translation, 
        string $changeType = 'update', 
        ?string $comment = null,
        ?string $previousContent = null
    ): TranslationVersion {
        $author = $this->getCurrentUser();
        
        $version = new TranslationVersion();
        $version->setTranslationKey($translation->getTranslationKey());
        $version->setLocale($translation->getLocale());
        $version->setContent($translation->getContent());
        $version->setDomain($translation->getDomain());
        $version->setChangeType($changeType);
        $version->setAuthor($author);
        $version->setChangeComment($comment);
        $version->setPreviousContent($previousContent);
        
        // Définir le numéro de version
        $nextVersion = $this->versionRepository->getNextVersionNumber(
            $translation->getTranslationKey(),
            $translation->getLocale(),
            $translation->getDomain()
        );
        $version->setVersionNumber($nextVersion);

        // Ajouter des métadonnées
        $version->addMetadata('ip_address', $this->getClientIp());
        $version->addMetadata('user_agent', $this->getUserAgent());
        $version->addMetadata('content_length', strlen($translation->getContent()));

        $this->entityManager->persist($version);
        $this->entityManager->flush();

        $this->logger->info('Translation version created', [
            'key' => $translation->getTranslationKey(),
            'locale' => $translation->getLocale(),
            'domain' => $translation->getDomain(),
            'version' => $nextVersion,
            'change_type' => $changeType,
            'author' => $author
        ]);

        // Nettoyer les anciennes versions si nécessaire
        $this->cleanupOldVersions(
            $translation->getTranslationKey(),
            $translation->getLocale(),
            $translation->getDomain()
        );

        return $version;
    }

    /**
     * Obtient l'historique complet d'une traduction
     */
    public function getTranslationHistory(string $key, string $locale, string $domain = 'messages', int $page = 1, int $limit = 20): array
    {
        $versions = $this->versionRepository->getTranslationHistory($key, $locale, $domain, $page, $limit);
        $totalVersions = $this->versionRepository->countVersionsForTranslation($key, $locale, $domain);

        return [
            'versions' => $versions,
            'total' => $totalVersions,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalVersions / $limit)
        ];
    }

    /**
     * Compare deux versions d'une traduction
     */
    public function compareVersions(int $version1Id, int $version2Id): array
    {
        return $this->versionRepository->compareVersions($version1Id, $version2Id);
    }

    /**
     * Restaure une version spécifique d'une traduction
     */
    public function restoreVersion(int $versionId, ?string $comment = null): TranslationVersion
    {
        $version = $this->versionRepository->find($versionId);
        
        if (!$version) {
            throw new \InvalidArgumentException("Version {$versionId} non trouvée");
        }

        $author = $this->getCurrentUser();
        $restoreComment = $comment ?? "Restauration de la version {$version->getVersionNumber()}";

        $restoredVersion = $this->versionRepository->restoreVersion($version, $author, $restoreComment);

        $this->logger->info('Translation version restored', [
            'original_version_id' => $versionId,
            'new_version_id' => $restoredVersion->getId(),
            'key' => $version->getTranslationKey(),
            'locale' => $version->getLocale(),
            'domain' => $version->getDomain(),
            'author' => $author
        ]);

        return $restoredVersion;
    }

    /**
     * Obtient une version spécifique d'une traduction
     */
    public function getVersion(string $key, string $locale, int $versionNumber, string $domain = 'messages'): ?TranslationVersion
    {
        return $this->versionRepository->findSpecificVersion($key, $locale, $versionNumber, $domain);
    }

    /**
     * Obtient la dernière version d'une traduction
     */
    public function getLatestVersion(string $key, string $locale, string $domain = 'messages'): ?TranslationVersion
    {
        return $this->versionRepository->findLatestVersion($key, $locale, $domain);
    }

    /**
     * Obtient toutes les versions d'une traduction
     */
    public function getAllVersions(string $key, string $locale, string $domain = 'messages'): array
    {
        return $this->versionRepository->findVersionsByTranslation($key, $locale, $domain);
    }

    /**
     * Obtient les statistiques de versionnement
     */
    public function getVersionStatistics(): array
    {
        return $this->versionRepository->getVersionStatistics();
    }

    /**
     * Obtient les traductions les plus modifiées
     */
    public function getMostModifiedTranslations(int $limit = 10): array
    {
        return $this->versionRepository->findMostModifiedTranslations($limit);
    }

    /**
     * Obtient l'activité récente par auteur
     */
    public function getRecentActivityByAuthor(string $author, int $limit = 50): array
    {
        return $this->versionRepository->findVersionsByAuthor($author, $limit);
    }

    /**
     * Obtient l'activité dans une période donnée
     */
    public function getActivityInDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->versionRepository->findVersionsByDateRange($startDate, $endDate);
    }

    /**
     * Nettoie les anciennes versions d'une traduction
     */
    public function cleanupOldVersions(string $key, string $locale, string $domain = 'messages'): int
    {
        return $this->versionRepository->cleanupOldVersions($key, $locale, $domain, $this->maxVersionsPerTranslation);
    }

    /**
     * Nettoie toutes les anciennes versions du système
     */
    public function cleanupAllOldVersions(): array
    {
        $translations = $this->entityManager->getRepository(Translation::class)->findAll();
        $results = [];

        foreach ($translations as $translation) {
            $deleted = $this->cleanupOldVersions(
                $translation->getTranslationKey(),
                $translation->getLocale(),
                $translation->getDomain()
            );
            
            if ($deleted > 0) {
                $results[] = [
                    'key' => $translation->getTranslationKey(),
                    'locale' => $translation->getLocale(),
                    'domain' => $translation->getDomain(),
                    'deleted_versions' => $deleted
                ];
            }
        }

        $this->logger->info('Cleanup completed', [
            'translations_cleaned' => count($results),
            'total_versions_deleted' => array_sum(array_column($results, 'deleted_versions'))
        ]);

        return $results;
    }

    /**
     * Exporte l'historique d'une traduction
     */
    public function exportTranslationHistory(string $key, string $locale, string $domain = 'messages', string $format = 'json'): string
    {
        $versions = $this->getAllVersions($key, $locale, $domain);
        
        $data = [
            'translation' => [
                'key' => $key,
                'locale' => $locale,
                'domain' => $domain
            ],
            'versions' => array_map(function (TranslationVersion $version) {
                return [
                    'version_number' => $version->getVersionNumber(),
                    'content' => $version->getContent(),
                    'change_type' => $version->getChangeType(),
                    'author' => $version->getAuthor(),
                    'comment' => $version->getChangeComment(),
                    'created_at' => $version->getCreatedAt()->format('Y-m-d H:i:s'),
                    'metadata' => $version->getMetadata()
                ];
            }, $versions),
            'exported_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ];

        return match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'yaml' => yaml_emit($data),
            'csv' => $this->exportToCsv($data['versions']),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}")
        };
    }

    /**
     * Importe l'historique d'une traduction
     */
    public function importTranslationHistory(string $data, string $format = 'json'): int
    {
        $parsedData = match ($format) {
            'json' => json_decode($data, true),
            'yaml' => yaml_parse($data),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}")
        };

        if (!isset($parsedData['versions']) || !is_array($parsedData['versions'])) {
            throw new \InvalidArgumentException('Format de données invalide');
        }

        $imported = 0;
        foreach ($parsedData['versions'] as $versionData) {
            $version = new TranslationVersion();
            $version->setTranslationKey($parsedData['translation']['key']);
            $version->setLocale($parsedData['translation']['locale']);
            $version->setDomain($parsedData['translation']['domain']);
            $version->setVersionNumber($versionData['version_number']);
            $version->setContent($versionData['content']);
            $version->setChangeType($versionData['change_type']);
            $version->setAuthor($versionData['author']);
            $version->setChangeComment($versionData['comment']);
            $version->setCreatedAt(new \DateTime($versionData['created_at']));
            $version->setMetadata($versionData['metadata'] ?? []);

            $this->entityManager->persist($version);
            $imported++;
        }

        $this->entityManager->flush();

        $this->logger->info('Translation history imported', [
            'key' => $parsedData['translation']['key'],
            'locale' => $parsedData['translation']['locale'],
            'domain' => $parsedData['translation']['domain'],
            'versions_imported' => $imported
        ]);

        return $imported;
    }

    /**
     * Définit le nombre maximum de versions à conserver par traduction
     */
    public function setMaxVersionsPerTranslation(int $maxVersions): void
    {
        $this->maxVersionsPerTranslation = $maxVersions;
    }

    /**
     * Obtient le nombre maximum de versions à conserver par traduction
     */
    public function getMaxVersionsPerTranslation(): int
    {
        return $this->maxVersionsPerTranslation;
    }

    /**
     * Obtient l'utilisateur actuel
     */
    private function getCurrentUser(): ?string
    {
        if (!$this->security) {
            return null;
        }

        $user = $this->security->getUser();
        return $user ? $user->getUserIdentifier() : null;
    }

    /**
     * Obtient l'adresse IP du client
     */
    private function getClientIp(): ?string
    {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return null;
        }

        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Obtient le User-Agent du client
     */
    private function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Exporte les versions au format CSV
     */
    private function exportToCsv(array $versions): string
    {
        $csv = "Version,Content,Change Type,Author,Comment,Created At\n";
        
        foreach ($versions as $version) {
            $csv .= sprintf(
                "%d,\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $version['version_number'],
                str_replace('"', '""', $version['content']),
                $version['change_type'],
                $version['author'] ?? '',
                str_replace('"', '""', $version['comment'] ?? ''),
                $version['created_at']
            );
        }

        return $csv;
    }
}

