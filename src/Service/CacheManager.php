<?php

namespace Dahovitech\TranslatorBundle\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Log\LoggerInterface;

class CacheManager
{
    private const CACHE_PREFIX = 'dahovitech_translator_';
    private const DEFAULT_TTL = 3600; // 1 heure

    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private int $cacheTtl = self::DEFAULT_TTL
    ) {
    }

    /**
     * Met en cache une traduction
     */
    public function cacheTranslation(string $key, string $locale, string $domain, string $content): void
    {
        $cacheKey = $this->generateCacheKey($key, $locale, $domain);
        
        try {
            $this->cache->get($cacheKey, function (ItemInterface $item) use ($content) {
                $item->expiresAfter($this->cacheTtl);
                return $content;
            });
            
            $this->logger->debug('Translation cached', [
                'cache_key' => $cacheKey,
                'content_length' => strlen($content)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache translation', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère une traduction depuis le cache
     */
    public function getCachedTranslation(string $key, string $locale, string $domain): ?string
    {
        $cacheKey = $this->generateCacheKey($key, $locale, $domain);
        
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) {
                // Si la clé n'existe pas dans le cache, on retourne null
                $item->expiresAfter($this->cacheTtl);
                return null;
            });
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve cached translation', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Invalide le cache pour une traduction spécifique
     */
    public function invalidateTranslation(string $key, string $locale, string $domain): bool
    {
        $cacheKey = $this->generateCacheKey($key, $locale, $domain);
        
        try {
            $this->cache->delete($cacheKey);
            $this->logger->debug('Translation cache invalidated', ['cache_key' => $cacheKey]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate translation cache', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalide tout le cache des traductions pour une locale
     */
    public function invalidateLocale(string $locale): bool
    {
        try {
            // Symfony cache ne supporte pas la suppression par pattern
            // On utilise une approche alternative avec des tags
            $this->cache->invalidateTags([self::CACHE_PREFIX . 'locale_' . $locale]);
            $this->logger->info('Locale cache invalidated', ['locale' => $locale]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate locale cache', [
                'locale' => $locale,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Invalide tout le cache des traductions pour un domaine
     */
    public function invalidateDomain(string $domain): bool
    {
        try {
            $this->cache->invalidateTags([self::CACHE_PREFIX . 'domain_' . $domain]);
            $this->logger->info('Domain cache invalidated', ['domain' => $domain]);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to invalidate domain cache', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vide complètement le cache des traductions
     */
    public function clearCache(): bool
    {
        try {
            $this->cache->clear();
            $this->logger->info('Translation cache cleared completely');
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear translation cache', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Met en cache plusieurs traductions en lot
     */
    public function cacheTranslations(array $translations, string $locale, string $domain): int
    {
        $cached = 0;
        
        foreach ($translations as $key => $content) {
            if (is_string($content)) {
                $this->cacheTranslation($key, $locale, $domain, $content);
                $cached++;
            }
        }
        
        $this->logger->info('Bulk translations cached', [
            'count' => $cached,
            'locale' => $locale,
            'domain' => $domain
        ]);
        
        return $cached;
    }

    /**
     * Précharge le cache avec toutes les traductions d'une locale
     */
    public function preloadCache(string $locale, string $domain, array $translations): void
    {
        $this->logger->info('Starting cache preload', [
            'locale' => $locale,
            'domain' => $domain,
            'count' => count($translations)
        ]);
        
        $this->cacheTranslations($translations, $locale, $domain);
        
        $this->logger->info('Cache preload completed', [
            'locale' => $locale,
            'domain' => $domain
        ]);
    }

    /**
     * Obtient les statistiques du cache
     */
    public function getCacheStats(): array
    {
        // Note: Symfony cache interface ne fournit pas de méthode standard pour les stats
        // Cette méthode peut être étendue selon l'implémentation du cache utilisé
        return [
            'cache_prefix' => self::CACHE_PREFIX,
            'default_ttl' => $this->cacheTtl,
            'current_ttl' => $this->cacheTtl
        ];
    }

    /**
     * Génère une clé de cache unique
     */
    private function generateCacheKey(string $key, string $locale, string $domain): string
    {
        return self::CACHE_PREFIX . md5($key . '_' . $locale . '_' . $domain);
    }

    /**
     * Génère les tags de cache pour une traduction
     */
    private function generateCacheTags(string $locale, string $domain): array
    {
        return [
            self::CACHE_PREFIX . 'locale_' . $locale,
            self::CACHE_PREFIX . 'domain_' . $domain,
            self::CACHE_PREFIX . 'all'
        ];
    }

    /**
     * Met en cache avec des tags pour l'invalidation groupée
     */
    public function cacheTranslationWithTags(string $key, string $locale, string $domain, string $content): void
    {
        $cacheKey = $this->generateCacheKey($key, $locale, $domain);
        $tags = $this->generateCacheTags($locale, $domain);
        
        try {
            $this->cache->get($cacheKey, function (ItemInterface $item) use ($content, $tags) {
                $item->expiresAfter($this->cacheTtl);
                $item->tag($tags);
                return $content;
            });
            
            $this->logger->debug('Translation cached with tags', [
                'cache_key' => $cacheKey,
                'tags' => $tags,
                'content_length' => strlen($content)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cache translation with tags', [
                'cache_key' => $cacheKey,
                'tags' => $tags,
                'error' => $e->getMessage()
            ]);
        }
    }
}

