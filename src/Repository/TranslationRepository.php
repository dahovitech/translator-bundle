<?php

namespace Dahovitech\TranslatorBundle\Repository;

use Dahovitech\TranslatorBundle\Entity\Translation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Translation::class);
    }

    /**
     * Trouve une traduction par clé, locale et domaine
     */
    public function findByKeyLocaleAndDomain(string $key, string $locale, string $domain = 'messages'): ?Translation
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.translationKey = :key')
            ->andWhere('t.locale = :locale')
            ->andWhere('t.domain = :domain')
            ->setParameter('key', $key)
            ->setParameter('locale', $locale)
            ->setParameter('domain', $domain)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les traductions pour une locale donnée
     */
    public function findByLocale(string $locale): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.locale = :locale')
            ->setParameter('locale', $locale)
            ->orderBy('t.domain', 'ASC')
            ->addOrderBy('t.translationKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les traductions pour un domaine donné
     */
    public function findByDomain(string $domain): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.domain = :domain')
            ->setParameter('domain', $domain)
            ->orderBy('t.locale', 'ASC')
            ->addOrderBy('t.translationKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les clés de traduction uniques
     */
    public function findUniqueKeys(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.translationKey')
            ->orderBy('t.translationKey', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Trouve toutes les locales disponibles
     */
    public function findAvailableLocales(): array
    {
        return $this->createQueryBuilder('t')
            ->select('DISTINCT t.locale')
            ->orderBy('t.locale', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Trouve toutes les traductions manquantes pour une locale
     */
    public function findMissingTranslations(string $locale, string $referenceLocale = 'en'): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.translationKey, t.domain')
            ->andWhere('t.locale = :referenceLocale')
            ->andWhere('NOT EXISTS (
                SELECT t2.id FROM ' . Translation::class . ' t2 
                WHERE t2.translationKey = t.translationKey 
                AND t2.locale = :locale 
                AND t2.domain = t.domain
            )')
            ->setParameter('referenceLocale', $referenceLocale)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();
    }
}

