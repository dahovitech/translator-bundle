<?php

namespace Dahovitech\TranslatorBundle\Tests\Unit\Service;

use Dahovitech\TranslatorBundle\Service\CacheManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheManagerTest extends TestCase
{
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheManager = new CacheManager($this->cache, $this->logger, 3600);
    }

    public function testCacheTranslation(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';
        $content = 'Test content';

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->isType('string'), $this->isType('callable'))
            ->willReturn($content);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Translation cached', $this->isType('array'));

        $this->cacheManager->cacheTranslation($key, $locale, $domain, $content);
    }

    public function testGetCachedTranslation(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';
        $expectedContent = 'Cached content';

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->isType('string'), $this->isType('callable'))
            ->willReturn($expectedContent);

        $result = $this->cacheManager->getCachedTranslation($key, $locale, $domain);

        $this->assertEquals($expectedContent, $result);
    }

    public function testInvalidateTranslation(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $this->cache->expects($this->once())
            ->method('delete')
            ->with($this->isType('string'))
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Translation cache invalidated', $this->isType('array'));

        $result = $this->cacheManager->invalidateTranslation($key, $locale, $domain);

        $this->assertTrue($result);
    }

    public function testClearCache(): void
    {
        $this->cache->expects($this->once())
            ->method('clear')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Translation cache cleared completely');

        $result = $this->cacheManager->clearCache();

        $this->assertTrue($result);
    }

    public function testCacheTranslations(): void
    {
        $translations = [
            'key1' => 'content1',
            'key2' => 'content2',
            'key3' => 'content3'
        ];
        $locale = 'fr';
        $domain = 'messages';

        $this->cache->expects($this->exactly(3))
            ->method('get')
            ->willReturn('content');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Bulk translations cached', $this->isType('array'));

        $result = $this->cacheManager->cacheTranslations($translations, $locale, $domain);

        $this->assertEquals(3, $result);
    }

    public function testGetCacheStats(): void
    {
        $stats = $this->cacheManager->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('cache_prefix', $stats);
        $this->assertArrayHasKey('default_ttl', $stats);
        $this->assertArrayHasKey('current_ttl', $stats);
    }

    public function testCacheTranslationWithTags(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';
        $content = 'Test content';

        $this->cache->expects($this->once())
            ->method('get')
            ->with($this->isType('string'), $this->isType('callable'))
            ->willReturn($content);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Translation cached with tags', $this->isType('array'));

        $this->cacheManager->cacheTranslationWithTags($key, $locale, $domain, $content);
    }

    public function testInvalidateLocale(): void
    {
        $locale = 'fr';

        $this->cache->expects($this->once())
            ->method('invalidateTags')
            ->with($this->isType('array'))
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Locale cache invalidated', $this->isType('array'));

        $result = $this->cacheManager->invalidateLocale($locale);

        $this->assertTrue($result);
    }

    public function testInvalidateDomain(): void
    {
        $domain = 'messages';

        $this->cache->expects($this->once())
            ->method('invalidateTags')
            ->with($this->isType('array'))
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Domain cache invalidated', $this->isType('array'));

        $result = $this->cacheManager->invalidateDomain($domain);

        $this->assertTrue($result);
    }

    public function testPreloadCache(): void
    {
        $locale = 'fr';
        $domain = 'messages';
        $translations = [
            'key1' => 'content1',
            'key2' => 'content2'
        ];

        $this->cache->expects($this->exactly(2))
            ->method('get')
            ->willReturn('content');

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $this->cacheManager->preloadCache($locale, $domain, $translations);
    }

    public function testCacheErrorHandling(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';
        $content = 'Test content';

        $this->cache->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('Cache error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to cache translation', $this->isType('array'));

        // Should not throw exception
        $this->cacheManager->cacheTranslation($key, $locale, $domain, $content);
    }

    public function testGetCachedTranslationErrorHandling(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $this->cache->expects($this->once())
            ->method('get')
            ->willThrowException(new \Exception('Cache error'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to retrieve cached translation', $this->isType('array'));

        $result = $this->cacheManager->getCachedTranslation($key, $locale, $domain);

        $this->assertNull($result);
    }
}

