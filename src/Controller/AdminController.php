<?php

namespace Dahovitech\TranslatorBundle\Controller;

use Dahovitech\TranslatorBundle\Service\TranslationManager;
use Dahovitech\TranslatorBundle\Service\FileFormatManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/translations', name: 'dahovitech_translator_admin_')]
class AdminController extends AbstractController
{
    public function __construct(
        private TranslationManager $translationManager,
        private FileFormatManager $fileFormatManager
    ) {
    }

    /**
     * Page d'accueil de l'administration des traductions
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $locales = $this->translationManager->getAvailableLocales();
        $stats = [
            'total_translations' => count($this->translationManager->getTranslationKeys()),
            'locales_count' => count($locales),
            'cache_enabled' => $this->translationManager->isCacheEnabled(),
            'cache_stats' => $this->translationManager->getCacheStats()
        ];

        return $this->render('@DahovitechTranslator/admin/index.html.twig', [
            'stats' => $stats,
            'locales' => $locales
        ]);
    }

    /**
     * Liste des traductions avec filtres
     */
    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $locale = $request->query->get('locale', 'en');
        $domain = $request->query->get('domain', 'messages');
        $search = $request->query->get('search', '');

        $translations = $this->translationManager->exportTranslations($locale, $domain);
        
        // Filtrer par recherche si nécessaire
        if ($search) {
            $translations = array_filter($translations, function($key) use ($search) {
                return stripos($key, $search) !== false;
            }, ARRAY_FILTER_USE_KEY);
        }

        return $this->render('@DahovitechTranslator/admin/list.html.twig', [
            'translations' => $translations,
            'locale' => $locale,
            'domain' => $domain,
            'search' => $search,
            'locales' => $this->translationManager->getAvailableLocales()
        ]);
    }

    /**
     * Formulaire d'édition d'une traduction
     */
    #[Route('/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $key = $request->query->get('key');
        $locale = $request->query->get('locale', 'en');
        $domain = $request->query->get('domain', 'messages');

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            
            if ($key && $content !== null) {
                $this->translationManager->setTranslation($key, $locale, $content, $domain);
                $this->addFlash('success', 'Traduction mise à jour avec succès');
                
                return $this->redirectToRoute('dahovitech_translator_admin_list', [
                    'locale' => $locale,
                    'domain' => $domain
                ]);
            }
        }

        $currentContent = $key ? $this->translationManager->getTranslation($key, $locale, $domain) : '';

        return $this->render('@DahovitechTranslator/admin/edit.html.twig', [
            'key' => $key,
            'locale' => $locale,
            'domain' => $domain,
            'content' => $currentContent,
            'locales' => $this->translationManager->getAvailableLocales()
        ]);
    }

    /**
     * Suppression d'une traduction
     */
    #[Route('/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $key = $request->request->get('key');
        $locale = $request->request->get('locale');
        $domain = $request->request->get('domain', 'messages');

        if (!$key || !$locale) {
            return new JsonResponse(['success' => false, 'message' => 'Paramètres manquants']);
        }

        $deleted = $this->translationManager->removeTranslation($key, $locale, $domain);

        return new JsonResponse([
            'success' => $deleted,
            'message' => $deleted ? 'Traduction supprimée' : 'Traduction non trouvée'
        ]);
    }

    /**
     * Import de fichiers de traduction
     */
    #[Route('/import', name: 'import', methods: ['GET', 'POST'])]
    public function import(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $locale = $request->request->get('locale');
            $domain = $request->request->get('domain', 'messages');
            $overwrite = $request->request->getBoolean('overwrite', false);
            
            /** @var UploadedFile $file */
            $file = $request->files->get('file');
            
            if ($file && $file->isValid()) {
                try {
                    $tempPath = $file->getPathname();
                    $imported = $this->translationManager->importFromFile($tempPath, $locale, $domain, $overwrite);
                    
                    $this->addFlash('success', "{$imported} traductions importées avec succès");
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Fichier invalide ou manquant');
            }
            
            return $this->redirectToRoute('dahovitech_translator_admin_import');
        }

        return $this->render('@DahovitechTranslator/admin/import.html.twig', [
            'locales' => $this->translationManager->getAvailableLocales(),
            'supported_formats' => $this->translationManager->getSupportedFileFormats()
        ]);
    }

    /**
     * Export de traductions
     */
    #[Route('/export', name: 'export', methods: ['GET', 'POST'])]
    public function export(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $locale = $request->request->get('locale');
            $domain = $request->request->get('domain', 'messages');
            $format = $request->request->get('format', 'yaml');
            
            $translations = $this->translationManager->exportTranslations($locale, $domain);
            
            if (empty($translations)) {
                $this->addFlash('warning', 'Aucune traduction trouvée pour cette locale et ce domaine');
                return $this->redirectToRoute('dahovitech_translator_admin_export');
            }
            
            // Créer un fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'translations_export_');
            $filename = "translations_{$locale}_{$domain}.{$format}";
            
            try {
                $this->fileFormatManager->exportToFile($translations, $tempFile, $format);
                
                $response = new Response(file_get_contents($tempFile));
                $response->headers->set('Content-Type', 'application/octet-stream');
                $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
                
                unlink($tempFile);
                
                return $response;
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'export: ' . $e->getMessage());
            }
        }

        return $this->render('@DahovitechTranslator/admin/export.html.twig', [
            'locales' => $this->translationManager->getAvailableLocales(),
            'supported_formats' => $this->translationManager->getSupportedFileFormats()
        ]);
    }

    /**
     * Gestion du cache
     */
    #[Route('/cache', name: 'cache', methods: ['GET', 'POST'])]
    public function cache(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            switch ($action) {
                case 'clear':
                    if ($this->translationManager->clearCache()) {
                        $this->addFlash('success', 'Cache vidé avec succès');
                    } else {
                        $this->addFlash('error', 'Erreur lors du vidage du cache');
                    }
                    break;
                    
                case 'preload':
                    $locale = $request->request->get('locale');
                    $domain = $request->request->get('domain', 'messages');
                    
                    if ($locale) {
                        $count = $this->translationManager->preloadCache($locale, $domain);
                        $this->addFlash('success', "{$count} traductions préchargées en cache");
                    }
                    break;
                    
                case 'invalidate_locale':
                    $locale = $request->request->get('locale');
                    if ($locale && $this->translationManager->invalidateLocaleCache($locale)) {
                        $this->addFlash('success', "Cache invalidé pour la locale {$locale}");
                    }
                    break;
            }
            
            return $this->redirectToRoute('dahovitech_translator_admin_cache');
        }

        return $this->render('@DahovitechTranslator/admin/cache.html.twig', [
            'cache_stats' => $this->translationManager->getCacheStats(),
            'cache_enabled' => $this->translationManager->isCacheEnabled(),
            'locales' => $this->translationManager->getAvailableLocales()
        ]);
    }

    /**
     * Détection des traductions manquantes
     */
    #[Route('/missing', name: 'missing', methods: ['GET'])]
    public function missing(Request $request): Response
    {
        $locale = $request->query->get('locale', 'fr');
        $referenceLocale = $request->query->get('reference', 'en');
        
        $missingTranslations = $this->translationManager->findMissingTranslations($locale, $referenceLocale);

        return $this->render('@DahovitechTranslator/admin/missing.html.twig', [
            'missing_translations' => $missingTranslations,
            'locale' => $locale,
            'reference_locale' => $referenceLocale,
            'locales' => $this->translationManager->getAvailableLocales()
        ]);
    }

    /**
     * API pour l'auto-complétion des clés
     */
    #[Route('/api/keys', name: 'api_keys', methods: ['GET'])]
    public function apiKeys(Request $request): JsonResponse
    {
        $search = $request->query->get('q', '');
        $keys = $this->translationManager->getTranslationKeys();
        
        if ($search) {
            $keys = array_filter($keys, function($key) use ($search) {
                return stripos($key, $search) !== false;
            });
        }
        
        return new JsonResponse(array_values($keys));
    }

    /**
     * API pour obtenir une traduction
     */
    #[Route('/api/translation', name: 'api_translation', methods: ['GET'])]
    public function apiTranslation(Request $request): JsonResponse
    {
        $key = $request->query->get('key');
        $locale = $request->query->get('locale');
        $domain = $request->query->get('domain', 'messages');
        
        if (!$key || !$locale) {
            return new JsonResponse(['error' => 'Paramètres manquants'], 400);
        }
        
        $content = $this->translationManager->getTranslation($key, $locale, $domain);
        
        return new JsonResponse([
            'key' => $key,
            'locale' => $locale,
            'domain' => $domain,
            'content' => $content,
            'exists' => $content !== null
        ]);
    }

    /**
     * Statistiques détaillées
     */
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(): Response
    {
        $locales = $this->translationManager->getAvailableLocales();
        $keys = $this->translationManager->getTranslationKeys();
        
        $stats = [
            'total_keys' => count($keys),
            'total_locales' => count($locales),
            'cache_enabled' => $this->translationManager->isCacheEnabled(),
            'cache_stats' => $this->translationManager->getCacheStats()
        ];
        
        // Statistiques par locale
        $localeStats = [];
        foreach ($locales as $locale) {
            $translations = $this->translationManager->exportTranslations($locale);
            $localeStats[$locale] = [
                'translation_count' => count($translations),
                'completion_rate' => count($keys) > 0 ? round((count($translations) / count($keys)) * 100, 2) : 0
            ];
        }
        
        return $this->render('@DahovitechTranslator/admin/stats.html.twig', [
            'stats' => $stats,
            'locale_stats' => $localeStats,
            'locales' => $locales
        ]);
    }
}

