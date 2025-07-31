<?php

namespace Dahovitech\TranslatorBundle\Tests\Unit\Service;

use Dahovitech\TranslatorBundle\Entity\Translation;
use Dahovitech\TranslatorBundle\Repository\TranslationRepository;
use Dahovitech\TranslatorBundle\Service\TranslationManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

class TranslationManagerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private TranslationRepository $repository;
    private TranslatorInterface $translator;
    private TranslationManager $translationManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(TranslationRepository::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        
        $this->translationManager = new TranslationManager(
            $this->entityManager,
            $this->repository,
            $this->translator
        );
    }

    public function testSetTranslationCreatesNewTranslation(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $content = 'Contenu de test';
        $domain = 'messages';

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Translation::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->translationManager->setTranslation($key, $locale, $content, $domain);

        $this->assertInstanceOf(Translation::class, $result);
        $this->assertEquals($key, $result->getTranslationKey());
        $this->assertEquals($locale, $result->getLocale());
        $this->assertEquals($content, $result->getContent());
        $this->assertEquals($domain, $result->getDomain());
    }

    public function testSetTranslationUpdatesExistingTranslation(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $content = 'Nouveau contenu';
        $domain = 'messages';

        $existingTranslation = new Translation();
        $existingTranslation->setTranslationKey($key);
        $existingTranslation->setLocale($locale);
        $existingTranslation->setContent('Ancien contenu');
        $existingTranslation->setDomain($domain);

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn($existingTranslation);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($existingTranslation);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->translationManager->setTranslation($key, $locale, $content, $domain);

        $this->assertEquals($content, $result->getContent());
    }

    public function testGetTranslationReturnsContent(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';
        $content = 'Contenu de test';

        $translation = new Translation();
        $translation->setContent($content);

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn($translation);

        $result = $this->translationManager->getTranslation($key, $locale, $domain);

        $this->assertEquals($content, $result);
    }

    public function testGetTranslationReturnsNullWhenNotFound(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn(null);

        $result = $this->translationManager->getTranslation($key, $locale, $domain);

        $this->assertNull($result);
    }

    public function testRemoveTranslationReturnsTrueWhenDeleted(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $translation = new Translation();

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn($translation);

        $this->entityManager
            ->expects($this->once())
            ->method('remove')
            ->with($translation);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->translationManager->removeTranslation($key, $locale, $domain);

        $this->assertTrue($result);
    }

    public function testRemoveTranslationReturnsFalseWhenNotFound(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn(null);

        $this->entityManager
            ->expects($this->never())
            ->method('remove');

        $result = $this->translationManager->removeTranslation($key, $locale, $domain);

        $this->assertFalse($result);
    }

    public function testImportTranslations(): void
    {
        $translations = [
            'key1' => 'Content 1',
            'key2' => 'Content 2',
            'key3' => 'Content 3'
        ];
        $locale = 'fr';
        $domain = 'messages';

        $this->repository
            ->method('findByKeyLocaleAndDomain')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->exactly(3))
            ->method('persist');

        $this->entityManager
            ->expects($this->exactly(3))
            ->method('flush');

        $count = $this->translationManager->importTranslations($translations, $locale, $domain);

        $this->assertEquals(3, $count);
    }

    public function testHasTranslationReturnsTrue(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $translation = new Translation();

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn($translation);

        $result = $this->translationManager->hasTranslation($key, $locale, $domain);

        $this->assertTrue($result);
    }

    public function testHasTranslationReturnsFalse(): void
    {
        $key = 'test.key';
        $locale = 'fr';
        $domain = 'messages';

        $this->repository
            ->expects($this->once())
            ->method('findByKeyLocaleAndDomain')
            ->with($key, $locale, $domain)
            ->willReturn(null);

        $result = $this->translationManager->hasTranslation($key, $locale, $domain);

        $this->assertFalse($result);
    }
}

