<?php

namespace Dahovitech\TranslatorBundle\Repository;

use Dahovitech\TranslatorBundle\Entity\TranslationVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class TranslationVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TranslationVersion::class);
    }

    /**
     * Trouve toutes les versions d'une traduction spécifique
     */
    public function findVersionsByTranslation(string $key, string $locale, string $domain = 'messages'): array
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->orderBy('tv.versionNumber', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve la dernière version d'une traduction
     */
    public function findLatestVersion(string $key, string $locale, string $domain = 'messages'): ?TranslationVersion
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->orderBy('tv.versionNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve une version spécifique d'une traduction
     */
    public function findSpecificVersion(string $key, string $locale, int $versionNumber, string $domain = 'messages'): ?TranslationVersion
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->andWhere('tv.versionNumber = :version')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->setParameter('version', $versionNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtient le prochain numéro de version pour une traduction
     */
    public function getNextVersionNumber(string $key, string $locale, string $domain = 'messages'): int
    {
        $result = $this->createQueryBuilder('tv')
            ->select('MAX(tv.versionNumber)')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleScalarResult();

        return ($result ?? 0) + 1;
    }

    /**
     * Trouve les versions créées par un auteur spécifique
     */
    public function findVersionsByAuthor(string $author, int $limit = 50): array
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('tv.author = :author')
            ->setParameter('author', $author)
            ->orderBy('tv.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les versions créées dans une période donnée
     */
    public function findVersionsByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('tv.createdAt >= :startDate')
            ->andWhere('tv.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('tv.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les versions par type de changement
     */
    public function findVersionsByChangeType(string $changeType, int $limit = 100): array
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('tv.changeType = :changeType')
            ->setParameter('changeType', $changeType)
            ->orderBy('tv.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtient l'historique complet d'une traduction avec pagination
     */
    public function getTranslationHistory(string $key, string $locale, string $domain = 'messages', int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('tv')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->orderBy('tv.versionNumber', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de versions pour une traduction
     */
    public function countVersionsForTranslation(string $key, string $locale, string $domain = 'messages'): int
    {
        return $this->createQueryBuilder('tv')
            ->select('COUNT(tv.id)')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les traductions les plus modifiées
     */
    public function findMostModifiedTranslations(int $limit = 10): array
    {
        return $this->createQueryBuilder('tv')
            ->select('tv.translationKey, tv.locale, tv.domain, COUNT(tv.id) as version_count')
            ->groupBy('tv.translationKey, tv.locale, tv.domain')
            ->orderBy('version_count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtient les statistiques des versions
     */
    public function getVersionStatistics(): array
    {
        $qb = $this->createQueryBuilder('tv');

        $totalVersions = $qb->select('COUNT(tv.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $versionsByType = $this->createQueryBuilder('tv')
            ->select('tv.changeType, COUNT(tv.id) as count')
            ->groupBy('tv.changeType')
            ->getQuery()
            ->getResult();

        $versionsByAuthor = $this->createQueryBuilder('tv')
            ->select('tv.author, COUNT(tv.id) as count')
            ->where('tv.author IS NOT NULL')
            ->groupBy('tv.author')
            ->orderBy('count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $recentActivity = $this->createQueryBuilder('tv')
            ->select('DATE(tv.createdAt) as date, COUNT(tv.id) as count')
            ->where('tv.createdAt >= :date')
            ->setParameter('date', new \DateTime('-30 days'))
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'total_versions' => $totalVersions,
            'versions_by_type' => $versionsByType,
            'versions_by_author' => $versionsByAuthor,
            'recent_activity' => $recentActivity
        ];
    }

    /**
     * Supprime les anciennes versions (garde seulement les N dernières)
     */
    public function cleanupOldVersions(string $key, string $locale, string $domain, int $keepVersions = 10): int
    {
        $versionsToKeep = $this->createQueryBuilder('tv')
            ->select('tv.id')
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->orderBy('tv.versionNumber', 'DESC')
            ->setMaxResults($keepVersions)
            ->getQuery()
            ->getResult();

        if (empty($versionsToKeep)) {
            return 0;
        }

        $idsToKeep = array_map(fn($v) => $v['id'], $versionsToKeep);

        return $this->createQueryBuilder('tv')
            ->delete()
            ->andWhere('tv.translationKey = :key')
            ->andWhere('tv.locale = :locale')
            ->andWhere('tv.domain = :domain')
            ->andWhere('tv.id NOT IN (:ids)')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->setParameter('ids', $idsToKeep)
            ->getQuery()
            ->execute();
    }

    /**
     * Trouve les versions avec des métadonnées spécifiques
     */
    public function findVersionsByMetadata(string $metadataKey, $metadataValue): array
    {
        return $this->createQueryBuilder('tv')
            ->andWhere('JSON_EXTRACT(tv.metadata, :path) = :value')
            ->setParameter('path', '$.' . $metadataKey)
            ->setParameter('value', $metadataValue)
            ->orderBy('tv.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compare deux versions et retourne les différences
     */
    public function compareVersions(int $version1Id, int $version2Id): array
    {
        $version1 = $this->find($version1Id);
        $version2 = $this->find($version2Id);

        if (!$version1 || !$version2) {
            throw new \InvalidArgumentException('Une ou plusieurs versions non trouvées');
        }

        return $version1->getDiffWith($version2);
    }

    /**
     * Restaure une version spécifique (crée une nouvelle version avec le contenu de l'ancienne)
     */
    public function restoreVersion(TranslationVersion $version, ?string $author = null, ?string $comment = null): TranslationVersion
    {
        $newVersion = new TranslationVersion();
        $newVersion->setTranslationKey($version->getTranslationKey());
        $newVersion->setLocale($version->getLocale());
        $newVersion->setContent($version->getContent());
        $newVersion->setDomain($version->getDomain());
        $newVersion->setChangeType('update');
        $newVersion->setAuthor($author);
        $newVersion->setChangeComment($comment ?? "Restauration de la version {$version->getVersionNumber()}");
        $newVersion->setVersionNumber($this->getNextVersionNumber(
            $version->getTranslationKey(),
            $version->getLocale(),
            $version->getDomain()
        ));

        $this->getEntityManager()->persist($newVersion);
        $this->getEntityManager()->flush();

        return $newVersion;
    }
}

