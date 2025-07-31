<?php

namespace Dahovitech\TranslatorBundle\Controller;

use Dahovitech\TranslatorBundle\Service\TranslationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/translations', name: 'dahovitech_translation_')]
class TranslationController extends AbstractController
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');
        $domain = $request->query->get('domain', 'messages');

        if (!$locale) {
            return new JsonResponse(['error' => 'Locale parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $translations = $this->translationManager->exportTranslations($locale, $domain);

        return new JsonResponse([
            'locale' => $locale,
            'domain' => $domain,
            'translations' => $translations
        ]);
    }

    #[Route('/{key}', name: 'get', methods: ['GET'])]
    public function get(string $key, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');
        $domain = $request->query->get('domain', 'messages');

        if (!$locale) {
            return new JsonResponse(['error' => 'Locale parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $translation = $this->translationManager->getTranslation($key, $locale, $domain);

        if ($translation === null) {
            return new JsonResponse(['error' => 'Translation not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'key' => $key,
            'locale' => $locale,
            'domain' => $domain,
            'content' => $translation
        ]);
    }

    #[Route('/{key}', name: 'set', methods: ['POST', 'PUT'])]
    public function set(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['locale']) || !isset($data['content'])) {
            return new JsonResponse(['error' => 'Locale and content are required'], Response::HTTP_BAD_REQUEST);
        }

        $locale = $data['locale'];
        $content = $data['content'];
        $domain = $data['domain'] ?? 'messages';

        $translation = $this->translationManager->setTranslation($key, $locale, $content, $domain);

        return new JsonResponse([
            'id' => $translation->getId(),
            'key' => $key,
            'locale' => $locale,
            'domain' => $domain,
            'content' => $content,
            'created_at' => $translation->getCreatedAt()->format('c'),
            'updated_at' => $translation->getUpdatedAt()->format('c')
        ], Response::HTTP_CREATED);
    }

    #[Route('/{key}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $key, Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');
        $domain = $request->query->get('domain', 'messages');

        if (!$locale) {
            return new JsonResponse(['error' => 'Locale parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $deleted = $this->translationManager->removeTranslation($key, $locale, $domain);

        if (!$deleted) {
            return new JsonResponse(['error' => 'Translation not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['message' => 'Translation deleted successfully']);
    }

    #[Route('/import', name: 'import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['locale']) || !isset($data['translations'])) {
            return new JsonResponse(['error' => 'Locale and translations are required'], Response::HTTP_BAD_REQUEST);
        }

        $locale = $data['locale'];
        $translations = $data['translations'];
        $domain = $data['domain'] ?? 'messages';

        $count = $this->translationManager->importTranslations($translations, $locale, $domain);

        return new JsonResponse([
            'message' => 'Translations imported successfully',
            'count' => $count,
            'locale' => $locale,
            'domain' => $domain
        ]);
    }

    #[Route('/export', name: 'export', methods: ['GET'])]
    public function export(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');
        $domain = $request->query->get('domain', 'messages');

        if (!$locale) {
            return new JsonResponse(['error' => 'Locale parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $translations = $this->translationManager->exportTranslations($locale, $domain);

        return new JsonResponse([
            'locale' => $locale,
            'domain' => $domain,
            'translations' => $translations
        ]);
    }

    #[Route('/locales', name: 'locales', methods: ['GET'])]
    public function locales(): JsonResponse
    {
        $locales = $this->translationManager->getAvailableLocales();

        return new JsonResponse(['locales' => $locales]);
    }

    #[Route('/keys', name: 'keys', methods: ['GET'])]
    public function keys(): JsonResponse
    {
        $keys = $this->translationManager->getTranslationKeys();

        return new JsonResponse(['keys' => $keys]);
    }

    #[Route('/missing', name: 'missing', methods: ['GET'])]
    public function missing(Request $request): JsonResponse
    {
        $locale = $request->query->get('locale');
        $referenceLocale = $request->query->get('reference_locale', 'en');

        if (!$locale) {
            return new JsonResponse(['error' => 'Locale parameter is required'], Response::HTTP_BAD_REQUEST);
        }

        $missing = $this->translationManager->findMissingTranslations($locale, $referenceLocale);

        return new JsonResponse([
            'locale' => $locale,
            'reference_locale' => $referenceLocale,
            'missing_translations' => $missing
        ]);
    }
}

