# DahovitechTranslatorBundle - Documentation Complète

**Version 2.0** - Bundle Symfony 7.3 pour la gestion avancée des traductions

---

## Table des Matières

1. [Introduction](#introduction)
2. [Nouvelles Fonctionnalités](#nouvelles-fonctionnalités)
3. [Installation et Configuration](#installation-et-configuration)
4. [Système de Cache](#système-de-cache)
5. [Support Multi-Formats](#support-multi-formats)
6. [Interface d'Administration](#interface-dadministration)
7. [Versionnement des Traductions](#versionnement-des-traductions)
8. [Détection Automatique des Clés](#détection-automatique-des-clés)
9. [Traduction Automatique](#traduction-automatique)
10. [API REST Avancée](#api-rest-avancée)
11. [Commandes Console](#commandes-console)
12. [Tests et Qualité](#tests-et-qualité)
13. [Migration depuis la Version 1.x](#migration-depuis-la-version-1x)
14. [Exemples d'Utilisation](#exemples-dutilisation)
15. [Dépannage](#dépannage)

---

## Introduction

Le DahovitechTranslatorBundle version 2.0 représente une évolution majeure du bundle de traduction Symfony original. Cette nouvelle version introduit des fonctionnalités avancées qui transforment la gestion des traductions en une expérience moderne, efficace et automatisée.

### Philosophie du Bundle

Le bundle a été conçu avec une philosophie centrée sur l'efficacité et l'automatisation. Plutôt que de simplement stocker et récupérer des traductions, il offre un écosystème complet qui inclut la mise en cache intelligente, le versionnement automatique, la détection proactive des clés manquantes, et l'intégration avec des services de traduction automatique de pointe.

### Architecture Technique

L'architecture du bundle repose sur plusieurs piliers fondamentaux qui garantissent sa robustesse et sa scalabilité. Le système de cache utilise l'infrastructure Symfony Cache pour optimiser les performances, tandis que le système de versionnement maintient un historique complet de toutes les modifications. L'interface d'administration offre une expérience utilisateur moderne avec une interface responsive construite avec Bootstrap 5 et des interactions JavaScript avancées.

---

## Nouvelles Fonctionnalités

### Système de Cache Avancé

Le nouveau système de cache représente une amélioration significative par rapport à la version précédente. Il utilise le composant Symfony Cache avec support des tags pour permettre une invalidation granulaire. Le cache peut être configuré avec différents adaptateurs (Redis, Memcached, APCu) selon les besoins de performance.

Le système de cache intelligent analyse les patterns d'utilisation des traductions et précharge automatiquement les clés les plus fréquemment utilisées. Il supporte également la mise en cache par domaine et par locale, permettant une invalidation ciblée lors des mises à jour.

### Support Multi-Formats Étendu

La prise en charge des formats de fichiers a été considérablement élargie. Le bundle supporte maintenant nativement YAML, JSON, XLIFF (versions 1.2 et 2.0), et PHP. Chaque format bénéficie d'un parser optimisé qui préserve la structure hiérarchique des clés tout en permettant une conversion transparente entre les formats.

Le système de conversion automatique permet de migrer facilement d'un format à un autre sans perte de données. Les métadonnées spécifiques à chaque format sont préservées lors des conversions, garantissant l'intégrité des données de traduction.

### Interface d'Administration Moderne

L'interface d'administration a été entièrement repensée avec une approche mobile-first. Elle utilise Bootstrap 5 pour garantir une compatibilité parfaite sur tous les appareils. L'interface inclut des fonctionnalités avancées comme la recherche en temps réel, l'édition en ligne, la gestion par lots, et des tableaux de bord avec des métriques détaillées.

L'interface supporte également la collaboration multi-utilisateurs avec un système de permissions granulaires. Les utilisateurs peuvent être assignés à des domaines ou des locales spécifiques, permettant une gestion décentralisée des traductions dans les grandes organisations.

### Versionnement Complet

Le système de versionnement maintient un historique complet de toutes les modifications apportées aux traductions. Chaque changement est horodaté, attribué à un utilisateur, et peut inclure un commentaire explicatif. Le système permet de comparer différentes versions, de restaurer des versions antérieures, et de suivre l'évolution des traductions dans le temps.

Les métadonnées de versionnement incluent des informations contextuelles comme l'adresse IP, le navigateur utilisé, et la taille du contenu. Ces informations facilitent l'audit et le débogage des modifications de traductions.

### Détection Automatique des Clés

Le système de détection automatique scanne le code source pour identifier les clés de traduction utilisées. Il supporte multiple syntaxes et frameworks, incluant les appels Symfony standard, les templates Twig, et le JavaScript. Le système peut détecter les clés manquantes, les clés orphelines, et générer des rapports détaillés sur l'utilisation des traductions.

La détection utilise des expressions régulières optimisées et peut être configurée pour supporter des patterns personnalisés. Elle inclut également des mécanismes d'exclusion pour éviter les faux positifs sur les variables dynamiques ou les URLs.

### Intégration de Traduction Automatique

L'intégration avec des services de traduction automatique permet d'accélérer significativement le processus de localisation. Le bundle supporte Google Translate, DeepL, et LibreTranslate, avec une architecture extensible pour ajouter d'autres providers.

Le système de traduction automatique inclut des mécanismes de validation pour vérifier la qualité des traductions générées. Il peut détecter les placeholders manquants, les traductions anormalement longues ou courtes, et les textes qui semblent contenir encore des éléments de la langue source.

---

## Installation et Configuration

### Prérequis Système

Le bundle nécessite PHP 8.1 ou supérieur et Symfony 7.3+. Il est compatible avec toutes les bases de données supportées par Doctrine ORM. Pour les fonctionnalités de cache avancées, il est recommandé d'utiliser Redis ou Memcached en production.

### Installation via Composer

```bash
composer require dahovitech/translator-bundle:^2.0
```

### Configuration du Bundle

La configuration du bundle se fait via le fichier `config/packages/dahovitech_translator.yaml`. Voici une configuration complète avec toutes les options disponibles :

```yaml
dahovitech_translator:
    # Configuration de base
    locales: ['en', 'fr', 'es', 'de', 'it']
    default_locale: 'en'
    fallback_locale: 'en'
    domains: ['messages', 'validators', 'security', 'admin']
    
    # Configuration de l'API
    enable_api: true
    api_prefix: '/api/translations'
    
    # Configuration du cache
    enable_cache: true
    cache_ttl: 3600
    cache_adapter: 'cache.app'  # ou 'cache.redis', 'cache.memcached'
    
    # Configuration du versionnement
    enable_versioning: true
    max_versions_per_translation: 50
    
    # Configuration de l'interface d'administration
    enable_admin: true
    admin_route_prefix: '/admin/translations'
    
    # Configuration de la traduction automatique
    auto_translation:
        enabled: true
        default_provider: 'google'
        providers:
            google:
                api_key: '%env(GOOGLE_TRANSLATE_API_KEY)%'
            deepl:
                api_key: '%env(DEEPL_API_KEY)%'
                free_tier: true
            libre:
                url: 'https://libretranslate.com'
                api_key: '%env(LIBRETRANSLATE_API_KEY)%'
    
    # Configuration de la détection automatique
    key_detection:
        enabled: true
        scan_directories: ['src', 'templates', 'assets']
        file_extensions: ['php', 'twig', 'js', 'ts', 'yaml', 'yml']
        exclude_patterns:
            - '/vendor/'
            - '/var/'
            - '/node_modules/'
    
    # Configuration de l'import/export
    import:
        sources: []
        overwrite_existing: false
        auto_detect_format: true
    
    export:
        format: 'yaml'
        output_dir: '%kernel.project_dir%/translations'
        include_metadata: true
```

### Configuration de la Base de Données

Le bundle utilise Doctrine ORM pour la persistance. Après l'installation, créez et exécutez les migrations :

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### Configuration du Cache

Pour optimiser les performances, configurez un adaptateur de cache approprié dans `config/packages/cache.yaml` :

```yaml
framework:
    cache:
        pools:
            cache.translator:
                adapter: cache.adapter.redis
                default_lifetime: 3600
                tags: true
```

---

## Système de Cache

### Architecture du Cache

Le système de cache du bundle utilise une architecture à plusieurs niveaux pour optimiser les performances. Le premier niveau utilise un cache en mémoire pour les traductions les plus fréquemment utilisées, tandis que le second niveau utilise un cache persistant (Redis, Memcached) pour les données moins critiques.

### Configuration Avancée

Le cache peut être configuré avec des stratégies différentes selon l'environnement :

```php
// Configuration programmatique du cache
$cacheManager = $container->get('dahovitech_translator.cache_manager');
$cacheManager->setCacheEnabled(true);

// Préchargement du cache pour une locale spécifique
$translationManager = $container->get('dahovitech_translator.translation_manager');
$translationManager->preloadCache('fr', 'messages');
```

### Stratégies d'Invalidation

Le système supporte plusieurs stratégies d'invalidation :

1. **Invalidation par clé** : Invalide une traduction spécifique
2. **Invalidation par locale** : Invalide toutes les traductions d'une langue
3. **Invalidation par domaine** : Invalide toutes les traductions d'un domaine
4. **Invalidation globale** : Vide complètement le cache

### Métriques et Monitoring

Le système de cache fournit des métriques détaillées accessibles via l'interface d'administration ou l'API :

```php
$stats = $translationManager->getCacheStats();
// Retourne : hit_rate, miss_rate, total_requests, cache_size, etc.
```

---

## Support Multi-Formats

### Formats Supportés

Le bundle supporte nativement plusieurs formats de fichiers de traduction :

#### YAML
Format privilégié pour sa lisibilité et sa structure hiérarchique :

```yaml
user:
    profile:
        name: "Nom d'utilisateur"
        email: "Adresse e-mail"
    actions:
        save: "Sauvegarder"
        cancel: "Annuler"
```

#### JSON
Format idéal pour les applications JavaScript :

```json
{
    "user": {
        "profile": {
            "name": "Nom d'utilisateur",
            "email": "Adresse e-mail"
        },
        "actions": {
            "save": "Sauvegarder",
            "cancel": "Annuler"
        }
    }
}
```

#### XLIFF
Format standard de l'industrie pour la traduction professionnelle :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<xliff version="1.2" xmlns="urn:oasis:names:tc:xliff:document:1.2">
    <file source-language="en" target-language="fr" datatype="plaintext">
        <body>
            <trans-unit id="user.profile.name">
                <source>Username</source>
                <target>Nom d'utilisateur</target>
            </trans-unit>
        </body>
    </file>
</xliff>
```

#### PHP
Format natif pour l'intégration directe :

```php
<?php
return [
    'user' => [
        'profile' => [
            'name' => 'Nom d\'utilisateur',
            'email' => 'Adresse e-mail',
        ],
        'actions' => [
            'save' => 'Sauvegarder',
            'cancel' => 'Annuler',
        ],
    ],
];
```

### Conversion Entre Formats

Le système de conversion permet de migrer facilement d'un format à un autre :

```php
$fileFormatManager = $container->get('dahovitech_translator.file_format_manager');

// Conversion YAML vers JSON
$fileFormatManager->convertFile(
    'translations/messages.fr.yaml',
    'translations/messages.fr.json'
);

// Conversion avec formats explicites
$fileFormatManager->convertFile(
    'source.yml',
    'target.xliff',
    'yaml',
    'xliff'
);
```

### Import et Export Avancés

Le système d'import/export supporte la validation automatique et la gestion des erreurs :

```php
// Import avec validation
$errors = $translationManager->validateTranslationFile('import.yaml');
if (empty($errors)) {
    $imported = $translationManager->importFromFile('import.yaml', 'fr', 'messages');
    echo "Imported {$imported} translations";
}

// Export vers plusieurs formats
$results = $translationManager->exportToMultipleFormats(
    'export/messages.fr',
    'fr',
    'messages',
    ['yaml', 'json', 'xliff']
);
```

---

## Interface d'Administration

### Vue d'Ensemble

L'interface d'administration offre une expérience utilisateur moderne et intuitive pour la gestion des traductions. Elle est construite avec Bootstrap 5 et utilise des technologies web modernes pour offrir une expérience fluide sur tous les appareils.

### Tableau de Bord

Le tableau de bord principal affiche des métriques en temps réel sur l'état des traductions :

- Nombre total de clés de traduction
- Langues supportées et leur statut de completion
- Statistiques du cache et performances
- Activité récente des utilisateurs
- Traductions les plus modifiées

### Gestion des Traductions

L'interface de gestion permet de :

- **Rechercher** des traductions avec des filtres avancés
- **Éditer** les traductions en ligne avec prévisualisation
- **Créer** de nouvelles traductions avec suggestions automatiques
- **Supprimer** des traductions avec confirmation
- **Comparer** différentes versions d'une traduction

### Fonctionnalités Avancées

#### Édition en Lot
L'interface permet de modifier plusieurs traductions simultanément :

```javascript
// Sélection multiple avec cases à cocher
// Opérations en lot : suppression, export, modification
// Barre de progression pour les opérations longues
```

#### Recherche Intelligente
Le système de recherche supporte :

- Recherche par clé avec autocomplétion
- Recherche par contenu avec mise en évidence
- Filtres par locale, domaine, et statut
- Recherche d'expressions régulières pour les utilisateurs avancés

#### Prévisualisation Contextuelle
Les traductions peuvent être prévisualisées dans leur contexte d'utilisation avec des captures d'écran automatiques des pages où elles apparaissent.

### Gestion des Utilisateurs et Permissions

L'interface supporte un système de permissions granulaires :

- **Administrateurs** : Accès complet à toutes les fonctionnalités
- **Traducteurs** : Accès limité aux locales assignées
- **Réviseurs** : Accès en lecture seule avec possibilité de commenter
- **Invités** : Accès en lecture seule aux traductions publiques

---

## Versionnement des Traductions

### Concept et Architecture

Le système de versionnement maintient un historique complet de toutes les modifications apportées aux traductions. Chaque modification crée une nouvelle version avec des métadonnées complètes sur le changement effectué.

### Structure des Versions

Chaque version contient :

```php
class TranslationVersion
{
    private int $versionNumber;           // Numéro séquentiel de version
    private string $content;              // Contenu de la traduction
    private string $changeType;           // 'create', 'update', 'delete'
    private ?string $author;              // Utilisateur ayant effectué le changement
    private ?string $changeComment;       // Commentaire explicatif
    private ?string $previousContent;     // Contenu précédent pour comparaison
    private \DateTimeInterface $createdAt; // Horodatage du changement
    private ?array $metadata;             // Métadonnées additionnelles
}
```

### Utilisation du Versionnement

#### Création Automatique de Versions

Le versionnement est automatique lors de toute modification :

```php
// Chaque modification crée automatiquement une version
$translationManager->setTranslation(
    'welcome.message',
    'fr',
    'Bienvenue sur notre nouveau site !',
    'messages'
);
// Une nouvelle version est automatiquement créée
```

#### Consultation de l'Historique

```php
$versionManager = $container->get('dahovitech_translator.version_manager');

// Obtenir toutes les versions d'une traduction
$versions = $versionManager->getAllVersions('welcome.message', 'fr', 'messages');

// Obtenir l'historique paginé
$history = $versionManager->getTranslationHistory('welcome.message', 'fr', 'messages', 1, 10);

// Comparer deux versions
$diff = $versionManager->compareVersions($version1Id, $version2Id);
```

#### Restauration de Versions

```php
// Restaurer une version spécifique
$restoredVersion = $versionManager->restoreVersion(
    $versionId,
    'Restauration suite à erreur de traduction'
);
```

### Nettoyage et Maintenance

Le système inclut des mécanismes de nettoyage automatique pour éviter l'accumulation excessive de versions :

```php
// Nettoyer les anciennes versions (garde les 50 dernières par défaut)
$versionManager->cleanupOldVersions('welcome.message', 'fr', 'messages');

// Nettoyage global de toutes les traductions
$results = $versionManager->cleanupAllOldVersions();
```

### Export et Import d'Historique

L'historique peut être exporté pour archivage ou migration :

```php
// Export de l'historique au format JSON
$historyJson = $versionManager->exportTranslationHistory(
    'welcome.message',
    'fr',
    'messages',
    'json'
);

// Import d'un historique
$imported = $versionManager->importTranslationHistory($historyJson, 'json');
```

---

## Détection Automatique des Clés

### Principe de Fonctionnement

Le système de détection automatique scanne le code source pour identifier toutes les clés de traduction utilisées dans l'application. Il utilise des expressions régulières optimisées pour détecter différents patterns d'utilisation selon les technologies employées.

### Patterns de Détection Supportés

#### PHP
```php
// Patterns détectés automatiquement :
$translator->trans('user.welcome');
$this->translator->trans('error.message');
trans('button.save');
t('quick.translation');
```

#### Twig
```twig
{# Patterns détectés automatiquement : #}
{{ 'user.profile.title'|trans }}
{% trans %}user.action.delete{% endtrans %}
{{ trans('form.validation.required') }}
```

#### JavaScript
```javascript
// Patterns détectés automatiquement :
Translator.trans('js.confirm.delete');
trans('notification.success');
i18n.t('menu.settings');
__('common.loading');
```

### Configuration de la Détection

```yaml
dahovitech_translator:
    key_detection:
        enabled: true
        scan_directories: ['src', 'templates', 'assets', 'public']
        file_extensions: ['php', 'twig', 'js', 'ts', 'vue', 'yaml']
        exclude_patterns:
            - '/vendor/'
            - '/var/cache/'
            - '/node_modules/'
            - '*.min.js'
        custom_patterns:
            php:
                - '/customTrans\([\'"]([^\'"\)]+)[\'"]\)/'
            twig:
                - '/\{\{\s*[\'"]([^\'"\}]+)[\'"]\s*\|\s*customFilter\s*\}\}/'
```

### Utilisation de la Détection

#### Via la Commande Console

```bash
# Détection basique
php bin/console dahovitech:translation:detect-keys

# Détection avec comparaison
php bin/console dahovitech:translation:detect-keys --locale=fr --domain=messages

# Affichage des clés manquantes uniquement
php bin/console dahovitech:translation:detect-keys --missing-only

# Création automatique des traductions manquantes
php bin/console dahovitech:translation:detect-keys --create-missing

# Export du rapport
php bin/console dahovitech:translation:detect-keys --format=json --output=report.json
```

#### Via l'API Programmatique

```php
$keyDetector = $container->get('dahovitech_translator.key_detector');

// Détection dans un projet
$detectedKeys = $keyDetector->detectTranslationKeys('/path/to/project');

// Détection dans un fichier spécifique
$fileKeys = $keyDetector->detectKeysInFile('src/Controller/UserController.php');

// Génération d'un rapport complet
$existingKeys = $translationManager->getTranslationKeys();
$report = $keyDetector->generateDetectionReport('/path/to/project', $existingKeys);
```

### Analyse et Rapports

Le système génère des rapports détaillés incluant :

- **Clés détectées** avec leur localisation dans le code
- **Clés manquantes** par rapport aux traductions existantes
- **Clés orphelines** présentes en base mais non utilisées
- **Statistiques** par type de fichier et par domaine
- **Taux de couverture** des traductions

#### Format de Rapport

```json
{
    "scan_info": {
        "project_path": "/path/to/project",
        "scan_date": "2024-01-15 14:30:00",
        "directories_scanned": ["src", "templates"],
        "file_extensions": ["php", "twig"]
    },
    "detected_keys": [
        {
            "key": "user.profile.name",
            "domain": "messages",
            "file_type": "twig",
            "file_path": "templates/user/profile.html.twig",
            "pattern": "/['\"]([^'\"\\|]+)['\"]\\s*\\|\\s*trans/"
        }
    ],
    "statistics": {
        "total_detected": 245,
        "unique_keys": 198,
        "by_file_type": {"php": 120, "twig": 78},
        "by_domain": {"messages": 150, "validators": 48}
    },
    "comparison": {
        "existing_keys_count": 180,
        "missing_keys": ["user.new.feature", "admin.dashboard.title"],
        "orphan_keys": ["old.unused.key"],
        "coverage_percentage": 91.2
    }
}
```

---

## Traduction Automatique

### Vue d'Ensemble

Le système de traduction automatique intègre plusieurs services de traduction de pointe pour accélérer le processus de localisation. Il supporte Google Translate, DeepL, et LibreTranslate, avec une architecture extensible pour ajouter d'autres providers.

### Configuration des Providers

#### Google Translate
```yaml
dahovitech_translator:
    auto_translation:
        providers:
            google:
                api_key: '%env(GOOGLE_TRANSLATE_API_KEY)%'
```

#### DeepL
```yaml
dahovitech_translator:
    auto_translation:
        providers:
            deepl:
                api_key: '%env(DEEPL_API_KEY)%'
                free_tier: true  # ou false pour l'API Pro
```

#### LibreTranslate
```yaml
dahovitech_translator:
    auto_translation:
        providers:
            libre:
                url: 'https://libretranslate.com'
                api_key: '%env(LIBRETRANSLATE_API_KEY)%'  # optionnel
```

### Utilisation de la Traduction Automatique

#### Traduction Simple

```php
$translationManager = $container->get('dahovitech_translator.translation_manager');

// Traduction automatique d'une clé spécifique
$translatedText = $translationManager->autoTranslate(
    'welcome.message',    // clé à traduire
    'en',                 // locale source
    'fr',                 // locale cible
    'messages',           // domaine
    'deepl'              // provider (optionnel)
);
```

#### Traduction en Lot

```php
// Traduire toutes les clés manquantes
$results = $translationManager->autoTranslateMissingKeys(
    'en',        // locale source
    'fr',        // locale cible
    'messages',  // domaine
    'google'     // provider
);

// Traduire un lot spécifique de traductions
$translations = [
    'button.save' => 'Save',
    'button.cancel' => 'Cancel',
    'form.required' => 'This field is required'
];

$translated = $translationManager->autoTranslateBatch(
    $translations,
    'en',
    'fr',
    'messages',
    'deepl'
);
```

#### Suggestions de Traduction

```php
// Obtenir des suggestions pour plusieurs locales
$suggestions = $translationManager->suggestTranslations(
    'user.welcome',
    'en',
    ['fr', 'es', 'de'],
    'messages',
    'deepl'
);

// Résultat :
// [
//     'fr' => [
//         'success' => true,
//         'suggested_text' => 'Bienvenue utilisateur',
//         'confidence' => 0.95,
//         'existing_translation' => null
//     ],
//     'es' => [
//         'success' => true,
//         'suggested_text' => 'Bienvenido usuario',
//         'confidence' => 0.92,
//         'existing_translation' => 'Usuario bienvenido'
//     ]
// ]
```

### Validation et Qualité

Le système inclut des mécanismes de validation pour assurer la qualité des traductions automatiques :

#### Validation Automatique

```php
$validation = $translationManager->validateAutoTranslation(
    'Hello {username}!',           // texte original
    'Bonjour {username} !',        // traduction proposée
    'en',                          // locale source
    'fr'                           // locale cible
);

// Résultat :
// [
//     'valid' => true,
//     'warnings' => [],
//     'suggestions' => []
// ]
```

#### Détection de Problèmes

Le système peut détecter :

- **Placeholders manquants** ou modifiés
- **Traductions anormalement longues** ou courtes
- **Texte non traduit** (reste dans la langue source)
- **Formatage HTML** corrompu
- **Variables et expressions** mal traduites

### Détection de Langue

```php
$autoTranslationService = $container->get('dahovitech_translator.auto_translation_service');

// Détecter la langue d'un texte
$detection = $autoTranslationService->detectLanguage(
    'Bonjour tout le monde !',
    'google'
);

// Résultat :
// [
//     'success' => true,
//     'detected_language' => 'fr',
//     'confidence' => 0.99
// ]
```

### Gestion des Erreurs et Fallbacks

Le système inclut une gestion robuste des erreurs avec fallbacks automatiques :

```php
// Configuration avec fallbacks
$autoTranslationService->setDefaultProvider('deepl');

// Si DeepL échoue, essayer Google Translate
$result = $autoTranslationService->translate(
    'Hello world',
    'fr',
    'en',
    'deepl'  // provider principal
);

if (!$result['success']) {
    // Fallback automatique vers Google
    $result = $autoTranslationService->translate(
        'Hello world',
        'fr',
        'en',
        'google'
    );
}
```

---

## API REST Avancée

### Endpoints Disponibles

L'API REST a été étendue pour supporter toutes les nouvelles fonctionnalités du bundle. Voici la liste complète des endpoints disponibles :

#### Gestion des Traductions

```http
GET    /api/translations                    # Liste des traductions
GET    /api/translations/{key}              # Récupérer une traduction
POST   /api/translations/{key}              # Créer/modifier une traduction
PUT    /api/translations/{key}              # Modifier une traduction
DELETE /api/translations/{key}              # Supprimer une traduction
```

#### Import/Export

```http
POST   /api/translations/import             # Importer des traductions
GET    /api/translations/export             # Exporter des traductions
POST   /api/translations/import/batch       # Import en lot
GET    /api/translations/export/formats     # Export multi-formats
```

#### Versionnement

```http
GET    /api/translations/{key}/versions     # Historique d'une traduction
GET    /api/translations/{key}/versions/{version} # Version spécifique
POST   /api/translations/{key}/versions/{version}/restore # Restaurer une version
GET    /api/translations/{key}/versions/compare/{v1}/{v2} # Comparer deux versions
```

#### Traduction Automatique

```http
POST   /api/translations/auto-translate     # Traduction automatique
POST   /api/translations/auto-translate/batch # Traduction en lot
GET    /api/translations/auto-translate/providers # Providers disponibles
POST   /api/translations/auto-translate/suggest # Suggestions de traduction
```

#### Cache

```http
DELETE /api/translations/cache              # Vider le cache
POST   /api/translations/cache/preload      # Précharger le cache
DELETE /api/translations/cache/locale/{locale} # Invalider cache locale
GET    /api/translations/cache/stats        # Statistiques du cache
```

#### Détection de Clés

```http
POST   /api/translations/detect-keys        # Détecter les clés
GET    /api/translations/missing-keys       # Clés manquantes
GET    /api/translations/orphan-keys        # Clés orphelines
POST   /api/translations/scan-project       # Scanner un projet
```

### Exemples d'Utilisation de l'API

#### Traduction Automatique via API

```bash
curl -X POST "http://your-app.com/api/translations/auto-translate" \
  -H "Content-Type: application/json" \
  -d '{
    "text": "Hello world",
    "source_locale": "en",
    "target_locale": "fr",
    "provider": "deepl"
  }'
```

Réponse :
```json
{
    "success": true,
    "translated_text": "Bonjour le monde",
    "detected_language": "en",
    "confidence": 0.99,
    "provider": "deepl"
}
```

#### Import de Traductions

```bash
curl -X POST "http://your-app.com/api/translations/import" \
  -H "Content-Type: multipart/form-data" \
  -F "file=@translations.yaml" \
  -F "locale=fr" \
  -F "domain=messages" \
  -F "overwrite=true"
```

#### Gestion du Cache

```bash
# Vider le cache
curl -X DELETE "http://your-app.com/api/translations/cache"

# Précharger le cache pour une locale
curl -X POST "http://your-app.com/api/translations/cache/preload" \
  -H "Content-Type: application/json" \
  -d '{
    "locale": "fr",
    "domain": "messages"
  }'
```

### Authentification et Sécurité

L'API supporte plusieurs méthodes d'authentification :

#### API Key
```http
Authorization: Bearer your-api-key
```

#### JWT Token
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Session Symfony
Utilise les sessions Symfony existantes pour l'authentification web.

### Pagination et Filtrage

L'API supporte la pagination et le filtrage avancé :

```http
GET /api/translations?page=2&limit=50&locale=fr&domain=messages&search=user
```

Réponse avec métadonnées de pagination :
```json
{
    "data": [...],
    "pagination": {
        "current_page": 2,
        "per_page": 50,
        "total": 1250,
        "total_pages": 25,
        "has_next": true,
        "has_previous": true
    },
    "filters": {
        "locale": "fr",
        "domain": "messages",
        "search": "user"
    }
}
```

### Gestion des Erreurs

L'API utilise des codes de statut HTTP standard et retourne des erreurs structurées :

```json
{
    "error": {
        "code": "TRANSLATION_NOT_FOUND",
        "message": "Translation not found for key 'invalid.key' in locale 'fr'",
        "details": {
            "key": "invalid.key",
            "locale": "fr",
            "domain": "messages"
        }
    }
}
```

---

## Commandes Console

### Vue d'Ensemble

Le bundle fournit un ensemble complet de commandes console pour automatiser les tâches de gestion des traductions. Ces commandes sont particulièrement utiles pour l'intégration dans des pipelines CI/CD et pour les tâches de maintenance.

### Commandes Disponibles

#### Détection de Clés

```bash
# Commande principale de détection
php bin/console dahovitech:translation:detect-keys [options] [path]

# Options disponibles :
--locale=LOCALE          # Locale pour la comparaison (défaut: en)
--domain=DOMAIN          # Domaine pour la comparaison (défaut: messages)
--output=FILE            # Fichier de sortie pour le rapport
--format=FORMAT          # Format de sortie (json, csv, table)
--missing-only           # Afficher seulement les clés manquantes
--orphan-only            # Afficher seulement les clés orphelines
--create-missing         # Créer automatiquement les traductions manquantes
--directories=DIR        # Répertoires à scanner (multiple)
--extensions=EXT         # Extensions de fichiers (multiple)
```

#### Gestion du Cache

```bash
# Vider le cache des traductions
php bin/console dahovitech:translation:cache:clear

# Précharger le cache
php bin/console dahovitech:translation:cache:preload --locale=fr --domain=messages

# Statistiques du cache
php bin/console dahovitech:translation:cache:stats

# Invalider le cache par locale
php bin/console dahovitech:translation:cache:invalidate --locale=fr
```

#### Import/Export

```bash
# Importer des traductions
php bin/console dahovitech:translation:import file.yaml --locale=fr --domain=messages

# Exporter des traductions
php bin/console dahovitech:translation:export --locale=fr --format=json --output=export.json

# Synchroniser avec un répertoire
php bin/console dahovitech:translation:sync /path/to/translations --locale=fr --bidirectional
```

#### Traduction Automatique

```bash
# Traduire automatiquement les clés manquantes
php bin/console dahovitech:translation:auto-translate --source=en --target=fr --provider=deepl

# Suggérer des traductions
php bin/console dahovitech:translation:suggest welcome.message --source=en --targets=fr,es,de

# Tester les providers
php bin/console dahovitech:translation:test-providers
```

#### Versionnement

```bash
# Afficher l'historique d'une traduction
php bin/console dahovitech:translation:history welcome.message --locale=fr

# Nettoyer les anciennes versions
php bin/console dahovitech:translation:cleanup-versions --keep=20

# Exporter l'historique
php bin/console dahovitech:translation:export-history welcome.message --locale=fr --format=json
```

#### Maintenance

```bash
# Vérifier l'intégrité des traductions
php bin/console dahovitech:translation:check-integrity

# Optimiser la base de données
php bin/console dahovitech:translation:optimize-database

# Générer des statistiques
php bin/console dahovitech:translation:stats --detailed
```

### Exemples d'Utilisation Avancée

#### Pipeline CI/CD

```bash
#!/bin/bash
# Script de déploiement avec vérification des traductions

# Détecter les nouvelles clés
php bin/console dahovitech:translation:detect-keys --missing-only --format=json --output=missing.json

# Vérifier s'il y a des clés manquantes
if [ -s missing.json ]; then
    echo "Nouvelles clés détectées, traduction automatique..."
    php bin/console dahovitech:translation:auto-translate --source=en --target=fr --provider=deepl
fi

# Vider le cache pour la production
php bin/console dahovitech:translation:cache:clear

# Précharger les traductions critiques
php bin/console dahovitech:translation:cache:preload --locale=fr --domain=messages
```

#### Maintenance Automatisée

```bash
#!/bin/bash
# Script de maintenance hebdomadaire

# Nettoyer les anciennes versions (garder 30 versions)
php bin/console dahovitech:translation:cleanup-versions --keep=30

# Optimiser la base de données
php bin/console dahovitech:translation:optimize-database

# Générer un rapport de santé
php bin/console dahovitech:translation:stats --detailed --output=health-report.json

# Détecter les clés orphelines
php bin/console dahovitech:translation:detect-keys --orphan-only --output=orphan-keys.txt
```

### Intégration avec des Outils Externes

#### Git Hooks

```bash
#!/bin/bash
# Pre-commit hook pour vérifier les traductions

# Détecter les nouvelles clés dans les fichiers modifiés
CHANGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep -E '\.(php|twig|js)$')

if [ ! -z "$CHANGED_FILES" ]; then
    # Scanner seulement les fichiers modifiés
    for file in $CHANGED_FILES; do
        php bin/console dahovitech:translation:detect-keys "$file" --missing-only
    done
fi
```

#### Monitoring

```bash
#!/bin/bash
# Script de monitoring pour alertes

# Vérifier le taux de couverture des traductions
COVERAGE=$(php bin/console dahovitech:translation:stats --format=json | jq '.coverage_percentage')

if (( $(echo "$COVERAGE < 90" | bc -l) )); then
    echo "ALERTE: Couverture des traductions faible ($COVERAGE%)"
    # Envoyer une notification
fi
```

---

## Tests et Qualité

### Architecture de Tests

Le bundle inclut une suite de tests complète couvrant tous les aspects du système. Les tests sont organisés en plusieurs catégories pour assurer une couverture maximale et une maintenance facile.

### Tests Unitaires

Les tests unitaires couvrent chaque service et composant individuellement :

#### Tests du CacheManager

```php
class CacheManagerTest extends TestCase
{
    public function testCacheTranslation(): void
    {
        $cacheManager = new CacheManager($this->cache, $this->logger, 3600);
        
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn('cached content');
            
        $cacheManager->cacheTranslation('test.key', 'fr', 'messages', 'content');
    }
    
    public function testInvalidateTranslation(): void
    {
        // Test de l'invalidation du cache
    }
    
    public function testCacheErrorHandling(): void
    {
        // Test de la gestion d'erreurs
    }
}
```

#### Tests du FileFormatManager

```php
class FileFormatManagerTest extends TestCase
{
    public function testImportFromYamlFile(): void
    {
        $yamlContent = "hello: Bonjour\ngoodbye: Au revoir";
        $filePath = $this->tempDir . '/test.yaml';
        file_put_contents($filePath, $yamlContent);

        $result = $this->fileFormatManager->importFromFile($filePath);

        $this->assertEquals('Bonjour', $result['hello']);
        $this->assertEquals('Au revoir', $result['goodbye']);
    }
}
```

### Tests d'Intégration

Les tests d'intégration vérifient l'interaction entre les différents composants :

```php
class TranslationManagerIntegrationTest extends KernelTestCase
{
    public function testCompleteTranslationWorkflow(): void
    {
        $container = self::getContainer();
        $translationManager = $container->get('dahovitech_translator.translation_manager');
        
        // Test du workflow complet : création, cache, versionnement
        $translation = $translationManager->setTranslation(
            'test.integration',
            'fr',
            'Test d\'intégration',
            'messages'
        );
        
        // Vérifier que la traduction est en cache
        $cached = $translationManager->getTranslation('test.integration', 'fr', 'messages');
        $this->assertEquals('Test d\'intégration', $cached);
        
        // Vérifier qu'une version a été créée
        $versions = $translationManager->getTranslationHistory('test.integration', 'fr', 'messages');
        $this->assertCount(1, $versions['versions']);
    }
}
```

### Tests Fonctionnels

Les tests fonctionnels vérifient le comportement de l'API et de l'interface web :

```php
class ApiTranslationTest extends WebTestCase
{
    public function testCreateTranslationViaApi(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/translations/test.api', [
            'json' => [
                'locale' => 'fr',
                'content' => 'Test API',
                'domain' => 'messages'
            ]
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
    
    public function testAutoTranslationApi(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/translations/auto-translate', [
            'json' => [
                'text' => 'Hello world',
                'source_locale' => 'en',
                'target_locale' => 'fr',
                'provider' => 'mock'
            ]
        ]);
        
        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($response['success']);
    }
}
```

### Tests de Performance

Des tests spécifiques vérifient les performances du système :

```php
class PerformanceTest extends TestCase
{
    public function testCachePerformance(): void
    {
        $start = microtime(true);
        
        // Simuler 1000 accès au cache
        for ($i = 0; $i < 1000; $i++) {
            $this->translationManager->getTranslation("test.key.{$i}", 'fr', 'messages');
        }
        
        $duration = microtime(true) - $start;
        $this->assertLessThan(1.0, $duration, 'Cache access should be under 1 second for 1000 requests');
    }
    
    public function testBulkImportPerformance(): void
    {
        $translations = [];
        for ($i = 0; $i < 10000; $i++) {
            $translations["key.{$i}"] = "Content {$i}";
        }
        
        $start = microtime(true);
        $this->translationManager->importTranslations($translations, 'fr', 'messages');
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(5.0, $duration, 'Bulk import should complete in under 5 seconds');
    }
}
```

### Couverture de Code

Le bundle maintient une couverture de code élevée :

```bash
# Générer le rapport de couverture
php bin/phpunit --coverage-html coverage/

# Vérifier la couverture minimale
php bin/phpunit --coverage-text --coverage-clover=coverage.xml
```

Objectifs de couverture :
- **Services principaux** : 95%+
- **Contrôleurs** : 90%+
- **Entités** : 85%+
- **Commandes** : 80%+

### Tests de Régression

Des tests automatisés vérifient que les nouvelles fonctionnalités n'introduisent pas de régressions :

```php
class RegressionTest extends TestCase
{
    /**
     * @dataProvider legacyTranslationProvider
     */
    public function testBackwardCompatibility($key, $locale, $expectedContent): void
    {
        // Vérifier que les anciennes traductions fonctionnent toujours
        $content = $this->translationManager->getTranslation($key, $locale);
        $this->assertEquals($expectedContent, $content);
    }
    
    public function legacyTranslationProvider(): array
    {
        return [
            ['legacy.key.1', 'fr', 'Contenu hérité 1'],
            ['legacy.key.2', 'en', 'Legacy content 2'],
            // ... autres cas de test
        ];
    }
}
```

### Qualité du Code

Le bundle utilise plusieurs outils pour maintenir la qualité du code :

#### PHPStan
```bash
# Analyse statique du code
vendor/bin/phpstan analyse src tests --level=8
```

#### PHP CS Fixer
```bash
# Formatage automatique du code
vendor/bin/php-cs-fixer fix src tests
```

#### Psalm
```bash
# Analyse de types avancée
vendor/bin/psalm --show-info=true
```

### Tests d'Acceptation

Des tests d'acceptation vérifient les scénarios utilisateur complets :

```php
class AcceptanceTest extends PantherTestCase
{
    public function testCompleteTranslationWorkflow(): void
    {
        $client = static::createPantherClient();
        
        // Naviguer vers l'interface d'administration
        $crawler = $client->request('GET', '/admin/translations');
        
        // Créer une nouvelle traduction
        $client->clickLink('Nouvelle traduction');
        $client->waitFor('#translation-form');
        
        $client->submitForm('Sauvegarder', [
            'key' => 'test.acceptance',
            'locale' => 'fr',
            'content' => 'Test d\'acceptation',
            'domain' => 'messages'
        ]);
        
        // Vérifier que la traduction apparaît dans la liste
        $this->assertSelectorTextContains('.translation-list', 'test.acceptance');
    }
}
```

---

## Migration depuis la Version 1.x

### Vue d'Ensemble de la Migration

La migration de la version 1.x vers la version 2.0 du DahovitechTranslatorBundle nécessite plusieurs étapes pour tirer parti des nouvelles fonctionnalités tout en préservant les données existantes. Cette section détaille le processus complet de migration.

### Prérequis de Migration

Avant de commencer la migration, assurez-vous que votre environnement répond aux exigences suivantes :

- **PHP 8.1+** (mise à jour depuis PHP 8.0 minimum de la v1.x)
- **Symfony 7.3+** (mise à jour depuis Symfony 6.4+ de la v1.x)
- **Doctrine ORM 3.0+**
- **Sauvegarde complète** de la base de données existante

### Étape 1 : Sauvegarde et Préparation

```bash
# Sauvegarder la base de données
mysqldump -u username -p database_name > backup_before_migration.sql

# Sauvegarder les fichiers de traduction existants
tar -czf translations_backup.tar.gz translations/

# Créer une branche de migration
git checkout -b migration-v2.0
```

### Étape 2 : Mise à Jour du Bundle

```bash
# Mettre à jour vers la version 2.0
composer require dahovitech/translator-bundle:^2.0

# Mettre à jour les dépendances
composer update
```

### Étape 3 : Migration de la Configuration

La configuration a été étendue avec de nouvelles options. Voici un exemple de migration :

#### Ancienne Configuration (v1.x)
```yaml
# config/packages/dahovitech_translator.yaml (v1.x)
dahovitech_translator:
    locales: ['en', 'fr', 'es']
    default_locale: 'en'
    fallback_locale: 'en'
    domains: ['messages', 'validators']
    enable_api: true
    enable_cache: true
    cache_ttl: 3600
```

#### Nouvelle Configuration (v2.0)
```yaml
# config/packages/dahovitech_translator.yaml (v2.0)
dahovitech_translator:
    # Configuration héritée (compatible)
    locales: ['en', 'fr', 'es']
    default_locale: 'en'
    fallback_locale: 'en'
    domains: ['messages', 'validators', 'security']
    enable_api: true
    
    # Configuration du cache étendue
    enable_cache: true
    cache_ttl: 3600
    cache_adapter: 'cache.app'
    
    # Nouvelles fonctionnalités
    enable_versioning: true
    max_versions_per_translation: 50
    
    enable_admin: true
    admin_route_prefix: '/admin/translations'
    
    auto_translation:
        enabled: false  # Désactivé par défaut lors de la migration
        default_provider: 'google'
        providers:
            google:
                api_key: '%env(GOOGLE_TRANSLATE_API_KEY)%'
    
    key_detection:
        enabled: true
        scan_directories: ['src', 'templates']
        file_extensions: ['php', 'twig']
```

### Étape 4 : Migration de la Base de Données

```bash
# Générer les nouvelles migrations
php bin/console doctrine:migrations:diff

# Examiner les migrations générées
cat migrations/VersionXXXXXXXXXXXX.php

# Exécuter les migrations
php bin/console doctrine:migrations:migrate
```

Les nouvelles tables créées :
- `dahovitech_translation_versions` : Historique des versions
- Nouveaux index sur la table existante pour optimiser les performances

### Étape 5 : Migration des Services Personnalisés

Si vous avez étendu ou personnalisé des services de la v1.x, voici comment les adapter :

#### Ancien Service Personnalisé (v1.x)
```php
class CustomTranslationService
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
    }
    
    public function customMethod(): void
    {
        // Ancienne logique
        $translation = $this->translationManager->getTranslation('key', 'fr');
    }
}
```

#### Service Migré (v2.0)
```php
class CustomTranslationService
{
    public function __construct(
        private TranslationManager $translationManager,
        private ?CacheManager $cacheManager = null,
        private ?VersionManager $versionManager = null
    ) {
    }
    
    public function customMethod(): void
    {
        // Logique mise à jour avec nouvelles fonctionnalités
        $translation = $this->translationManager->getTranslation('key', 'fr');
        
        // Utiliser les nouvelles fonctionnalités si disponibles
        if ($this->versionManager) {
            $history = $this->versionManager->getTranslationHistory('key', 'fr');
        }
    }
}
```

### Étape 6 : Migration des Données Existantes

#### Script de Migration des Versions

```php
<?php
// bin/migrate-to-v2.php

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateToV2Command extends Command
{
    protected static $defaultName = 'app:migrate-to-v2';
    
    public function __construct(
        private TranslationManager $translationManager,
        private VersionManager $versionManager
    ) {
        parent::__construct();
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Début de la migration vers v2.0...');
        
        // Créer des versions initiales pour toutes les traductions existantes
        $translations = $this->translationManager->getAllTranslations();
        
        foreach ($translations as $translation) {
            $this->versionManager->createVersion(
                $translation,
                'create',
                'Version initiale créée lors de la migration v2.0'
            );
        }
        
        $output->writeln('Migration terminée avec succès !');
        return Command::SUCCESS;
    }
}
```

### Étape 7 : Tests de Migration

```bash
# Exécuter les tests pour vérifier la compatibilité
php bin/phpunit

# Tester les nouvelles fonctionnalités
php bin/console dahovitech:translation:detect-keys --missing-only
php bin/console dahovitech:translation:cache:stats

# Vérifier l'interface d'administration
curl -I http://localhost/admin/translations
```

### Étape 8 : Optimisation Post-Migration

#### Préchargement du Cache
```bash
# Précharger le cache pour toutes les locales
for locale in en fr es; do
    php bin/console dahovitech:translation:cache:preload --locale=$locale
done
```

#### Optimisation de la Base de Données
```bash
# Optimiser les tables après migration
php bin/console doctrine:schema:update --dump-sql
php bin/console dahovitech:translation:optimize-database
```

### Problèmes Courants et Solutions

#### Problème : Erreur de Contrainte de Clé Étrangère

**Symptôme :** Erreur lors de la migration des tables
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solution :**
```bash
# Désactiver temporairement les contraintes
SET FOREIGN_KEY_CHECKS = 0;
# Exécuter la migration
php bin/console doctrine:migrations:migrate
# Réactiver les contraintes
SET FOREIGN_KEY_CHECKS = 1;
```

#### Problème : Cache Incompatible

**Symptôme :** Erreurs de cache après migration

**Solution :**
```bash
# Vider complètement le cache
php bin/console cache:clear
php bin/console dahovitech:translation:cache:clear

# Reconfigurer l'adaptateur de cache si nécessaire
```

#### Problème : Services Non Trouvés

**Symptôme :** Services de la v2.0 non disponibles

**Solution :**
```bash
# Vider le cache du container
php bin/console cache:clear

# Vérifier la configuration des services
php bin/console debug:container dahovitech_translator
```

### Rollback en Cas de Problème

Si la migration échoue, voici comment revenir à la version précédente :

```bash
# Restaurer la base de données
mysql -u username -p database_name < backup_before_migration.sql

# Revenir à la version précédente du bundle
composer require dahovitech/translator-bundle:^1.0

# Restaurer la configuration
git checkout HEAD~1 -- config/packages/dahovitech_translator.yaml

# Vider le cache
php bin/console cache:clear
```

### Validation de la Migration

#### Checklist de Validation

- [ ] Toutes les traductions existantes sont accessibles
- [ ] L'API REST fonctionne correctement
- [ ] Le cache est opérationnel
- [ ] L'interface d'administration est accessible
- [ ] Les nouvelles fonctionnalités sont disponibles
- [ ] Les performances sont maintenues ou améliorées
- [ ] Aucune régression détectée dans les tests

#### Tests de Validation

```php
class MigrationValidationTest extends TestCase
{
    public function testAllExistingTranslationsAccessible(): void
    {
        // Vérifier que toutes les traductions pré-migration sont accessibles
        $legacyTranslations = $this->getLegacyTranslationsList();
        
        foreach ($legacyTranslations as $key => $expectedContent) {
            $content = $this->translationManager->getTranslation($key, 'fr');
            $this->assertEquals($expectedContent, $content);
        }
    }
    
    public function testNewFeaturesAvailable(): void
    {
        // Vérifier que les nouvelles fonctionnalités sont disponibles
        $this->assertTrue($this->translationManager->isCacheEnabled());
        $this->assertInstanceOf(VersionManager::class, $this->versionManager);
    }
}
```

---

## Exemples d'Utilisation

### Scénarios d'Utilisation Courants

Cette section présente des exemples concrets d'utilisation du bundle dans différents contextes et pour différents besoins.

### Exemple 1 : Site E-commerce Multilingue

#### Contexte
Un site e-commerce qui doit supporter 5 langues (français, anglais, espagnol, allemand, italien) avec des traductions pour les produits, les catégories, et l'interface utilisateur.

#### Configuration
```yaml
# config/packages/dahovitech_translator.yaml
dahovitech_translator:
    locales: ['fr', 'en', 'es', 'de', 'it']
    default_locale: 'fr'
    fallback_locale: 'en'
    domains: ['messages', 'products', 'categories', 'checkout']
    
    enable_cache: true
    cache_ttl: 7200  # 2 heures pour un site e-commerce
    
    auto_translation:
        enabled: true
        default_provider: 'deepl'
        providers:
            deepl:
                api_key: '%env(DEEPL_API_KEY)%'
    
    key_detection:
        enabled: true
        scan_directories: ['src', 'templates', 'assets/js']
```

#### Utilisation dans les Contrôleurs
```php
class ProductController extends AbstractController
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
    }
    
    #[Route('/product/{id}', name: 'product_show')]
    public function show(Product $product, Request $request): Response
    {
        $locale = $request->getLocale();
        
        // Récupérer les traductions du produit
        $productName = $this->translationManager->getTranslation(
            "product.{$product->getId()}.name",
            $locale,
            'products'
        ) ?? $product->getName();
        
        $productDescription = $this->translationManager->getTranslation(
            "product.{$product->getId()}.description",
            $locale,
            'products'
        ) ?? $product->getDescription();
        
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'product_name' => $productName,
            'product_description' => $productDescription
        ]);
    }
    
    #[Route('/admin/product/{id}/translate', name: 'admin_product_translate')]
    public function autoTranslate(Product $product): JsonResponse
    {
        $sourceLocale = 'fr';
        $targetLocales = ['en', 'es', 'de', 'it'];
        
        $results = [];
        
        foreach ($targetLocales as $targetLocale) {
            // Traduire automatiquement le nom du produit
            $translatedName = $this->translationManager->autoTranslate(
                "product.{$product->getId()}.name",
                $sourceLocale,
                $targetLocale,
                'products',
                'deepl'
            );
            
            // Traduire automatiquement la description
            $translatedDescription = $this->translationManager->autoTranslate(
                "product.{$product->getId()}.description",
                $sourceLocale,
                $targetLocale,
                'products',
                'deepl'
            );
            
            $results[$targetLocale] = [
                'name' => $translatedName,
                'description' => $translatedDescription
            ];
        }
        
        return new JsonResponse(['success' => true, 'translations' => $results]);
    }
}
```

#### Templates Twig
```twig
{# templates/product/show.html.twig #}
<div class="product-details">
    <h1>{{ product_name }}</h1>
    <div class="product-description">
        {{ product_description|raw }}
    </div>
    
    <div class="product-actions">
        <button class="btn btn-primary">
            {{ 'product.actions.add_to_cart'|trans({}, 'products') }}
        </button>
        <button class="btn btn-secondary">
            {{ 'product.actions.add_to_wishlist'|trans({}, 'products') }}
        </button>
    </div>
    
    <div class="product-info">
        <span class="price">{{ 'product.price'|trans({}, 'products') }}: {{ product.price }}€</span>
        <span class="availability">
            {{ 'product.availability'|trans({}, 'products') }}: 
            {{ product.inStock ? 'product.in_stock'|trans({}, 'products') : 'product.out_of_stock'|trans({}, 'products') }}
        </span>
    </div>
</div>
```

#### Workflow de Traduction Automatisé
```bash
#!/bin/bash
# scripts/auto-translate-products.sh

# Détecter les nouvelles clés de produits
php bin/console dahovitech:translation:detect-keys templates/product/ --domain=products --missing-only

# Traduire automatiquement les clés manquantes
for locale in en es de it; do
    echo "Traduction automatique vers $locale..."
    php bin/console dahovitech:translation:auto-translate \
        --source=fr \
        --target=$locale \
        --domain=products \
        --provider=deepl
done

# Précharger le cache pour toutes les locales
for locale in fr en es de it; do
    php bin/console dahovitech:translation:cache:preload --locale=$locale --domain=products
done
```

### Exemple 2 : Application SaaS avec Interface d'Administration

#### Contexte
Une application SaaS qui permet aux clients de personnaliser les traductions de leur interface selon leurs besoins métier.

#### Architecture Multi-Tenant
```php
class TenantTranslationManager
{
    public function __construct(
        private TranslationManager $translationManager,
        private TenantContext $tenantContext
    ) {
    }
    
    public function getTenantTranslation(string $key, string $locale, string $domain = 'messages'): ?string
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        
        // Chercher d'abord une traduction spécifique au tenant
        $tenantKey = "tenant.{$tenant->getId()}.{$key}";
        $tenantTranslation = $this->translationManager->getTranslation($tenantKey, $locale, $domain);
        
        if ($tenantTranslation) {
            return $tenantTranslation;
        }
        
        // Fallback vers la traduction par défaut
        return $this->translationManager->getTranslation($key, $locale, $domain);
    }
    
    public function setTenantTranslation(string $key, string $locale, string $content, string $domain = 'messages'): void
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $tenantKey = "tenant.{$tenant->getId()}.{$key}";
        
        $this->translationManager->setTranslation($tenantKey, $locale, $content, $domain);
    }
}
```

#### Interface de Personnalisation
```php
#[Route('/admin/translations/customize', name: 'admin_translations_customize')]
class TranslationCustomizationController extends AbstractController
{
    public function index(Request $request, TenantTranslationManager $tenantTranslationManager): Response
    {
        $locale = $request->get('locale', 'fr');
        $domain = $request->get('domain', 'messages');
        
        // Récupérer toutes les clés de traduction disponibles
        $availableKeys = $this->translationManager->getTranslationKeys();
        
        // Récupérer les personnalisations existantes du tenant
        $customizations = [];
        foreach ($availableKeys as $key) {
            $defaultTranslation = $this->translationManager->getTranslation($key, $locale, $domain);
            $customTranslation = $tenantTranslationManager->getTenantTranslation($key, $locale, $domain);
            
            if ($customTranslation !== $defaultTranslation) {
                $customizations[$key] = [
                    'default' => $defaultTranslation,
                    'custom' => $customTranslation
                ];
            }
        }
        
        return $this->render('admin/translations/customize.html.twig', [
            'available_keys' => $availableKeys,
            'customizations' => $customizations,
            'locale' => $locale,
            'domain' => $domain
        ]);
    }
    
    #[Route('/admin/translations/customize/save', name: 'admin_translations_customize_save', methods: ['POST'])]
    public function save(Request $request, TenantTranslationManager $tenantTranslationManager): JsonResponse
    {
        $key = $request->request->get('key');
        $locale = $request->request->get('locale');
        $content = $request->request->get('content');
        $domain = $request->request->get('domain', 'messages');
        
        try {
            $tenantTranslationManager->setTenantTranslation($key, $locale, $content, $domain);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Personnalisation sauvegardée avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()
            ], 400);
        }
    }
}
```

### Exemple 3 : API Mobile avec Traductions Dynamiques

#### Contexte
Une API REST pour application mobile qui doit fournir des traductions dynamiques selon la langue de l'utilisateur et permettre la mise à jour en temps réel.

#### Contrôleur API
```php
#[Route('/api/v1/translations', name: 'api_translations_')]
class ApiTranslationController extends AbstractController
{
    public function __construct(
        private TranslationManager $translationManager,
        private SerializerInterface $serializer
    ) {
    }
    
    #[Route('/bundle/{locale}', name: 'bundle', methods: ['GET'])]
    public function getTranslationBundle(string $locale, Request $request): JsonResponse
    {
        $domain = $request->query->get('domain', 'messages');
        $version = $request->query->get('version');
        
        // Récupérer toutes les traductions pour la locale
        $translations = $this->translationManager->exportTranslations($locale, $domain);
        
        // Calculer un hash pour la détection de changements
        $currentHash = md5(serialize($translations));
        
        // Si le client a déjà cette version, retourner 304 Not Modified
        if ($version === $currentHash) {
            return new JsonResponse(null, 304);
        }
        
        return new JsonResponse([
            'locale' => $locale,
            'domain' => $domain,
            'version' => $currentHash,
            'translations' => $translations,
            'count' => count($translations),
            'generated_at' => (new \DateTime())->format('c')
        ]);
    }
    
    #[Route('/sync', name: 'sync', methods: ['POST'])]
    public function syncTranslations(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $locale = $data['locale'];
        $clientVersion = $data['version'] ?? null;
        $domains = $data['domains'] ?? ['messages'];
        
        $updates = [];
        
        foreach ($domains as $domain) {
            $translations = $this->translationManager->exportTranslations($locale, $domain);
            $currentHash = md5(serialize($translations));
            
            // Vérifier si des mises à jour sont nécessaires
            if ($clientVersion !== $currentHash) {
                $updates[$domain] = [
                    'version' => $currentHash,
                    'translations' => $translations
                ];
            }
        }
        
        return new JsonResponse([
            'has_updates' => !empty($updates),
            'updates' => $updates,
            'sync_timestamp' => time()
        ]);
    }
    
    #[Route('/suggest/{key}', name: 'suggest', methods: ['POST'])]
    public function suggestTranslation(string $key, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sourceLocale = $data['source_locale'];
        $targetLocales = $data['target_locales'];
        $domain = $data['domain'] ?? 'messages';
        
        try {
            $suggestions = $this->translationManager->suggestTranslations(
                $key,
                $sourceLocale,
                $targetLocales,
                $domain,
                'deepl'
            );
            
            return new JsonResponse([
                'success' => true,
                'key' => $key,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
```

#### Client Mobile (Exemple React Native)
```javascript
// TranslationService.js
class TranslationService {
    constructor(apiBaseUrl) {
        this.apiBaseUrl = apiBaseUrl;
        this.cache = new Map();
        this.currentVersion = null;
    }
    
    async loadTranslations(locale, domain = 'messages') {
        try {
            const url = `${this.apiBaseUrl}/api/v1/translations/bundle/${locale}?domain=${domain}&version=${this.currentVersion}`;
            const response = await fetch(url);
            
            if (response.status === 304) {
                // Pas de changements, utiliser le cache
                return this.cache.get(`${locale}_${domain}`);
            }
            
            const data = await response.json();
            
            // Mettre à jour le cache
            this.cache.set(`${locale}_${domain}`, data.translations);
            this.currentVersion = data.version;
            
            return data.translations;
        } catch (error) {
            console.error('Erreur lors du chargement des traductions:', error);
            return this.cache.get(`${locale}_${domain}`) || {};
        }
    }
    
    async syncTranslations(locale, domains = ['messages']) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/api/v1/translations/sync`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    locale,
                    domains,
                    version: this.currentVersion
                })
            });
            
            const data = await response.json();
            
            if (data.has_updates) {
                // Mettre à jour le cache avec les nouvelles traductions
                Object.entries(data.updates).forEach(([domain, update]) => {
                    this.cache.set(`${locale}_${domain}`, update.translations);
                });
            }
            
            return data.has_updates;
        } catch (error) {
            console.error('Erreur lors de la synchronisation:', error);
            return false;
        }
    }
    
    translate(key, locale, domain = 'messages', params = {}) {
        const translations = this.cache.get(`${locale}_${domain}`) || {};
        let translation = translations[key] || key;
        
        // Remplacer les paramètres
        Object.entries(params).forEach(([param, value]) => {
            translation = translation.replace(`{${param}}`, value);
        });
        
        return translation;
    }
}

// Utilisation dans un composant React Native
import React, { useEffect, useState } from 'react';
import { View, Text, Button } from 'react-native';

const translationService = new TranslationService('https://api.example.com');

const MyComponent = () => {
    const [locale, setLocale] = useState('fr');
    const [translations, setTranslations] = useState({});
    
    useEffect(() => {
        loadTranslations();
    }, [locale]);
    
    const loadTranslations = async () => {
        const translations = await translationService.loadTranslations(locale);
        setTranslations(translations);
    };
    
    const t = (key, params = {}) => {
        return translationService.translate(key, locale, 'messages', params);
    };
    
    return (
        <View>
            <Text>{t('welcome.title')}</Text>
            <Text>{t('user.greeting', { name: 'John' })}</Text>
            <Button 
                title={t('button.change_language')} 
                onPress={() => setLocale(locale === 'fr' ? 'en' : 'fr')}
            />
        </View>
    );
};
```

### Exemple 4 : Intégration CI/CD avec Détection Automatique

#### Contexte
Automatisation complète du processus de traduction dans un pipeline CI/CD avec détection automatique des nouvelles clés et traduction automatique.

#### Pipeline GitLab CI
```yaml
# .gitlab-ci.yml
stages:
  - test
  - detect-translations
  - auto-translate
  - deploy

variables:
  MYSQL_DATABASE: translator_test
  MYSQL_ROOT_PASSWORD: root

test:
  stage: test
  image: php:8.1
  services:
    - mysql:8.0
  before_script:
    - apt-get update && apt-get install -y git unzip
    - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    - composer install --no-dev --optimize-autoloader
  script:
    - php bin/phpunit
    - php bin/console doctrine:migrations:migrate --no-interaction

detect-new-translations:
  stage: detect-translations
  image: php:8.1
  services:
    - mysql:8.0
  before_script:
    - composer install --no-dev --optimize-autoloader
    - php bin/console doctrine:migrations:migrate --no-interaction
  script:
    - php bin/console dahovitech:translation:detect-keys --missing-only --format=json --output=missing-keys.json
    - php bin/console dahovitech:translation:detect-keys --orphan-only --format=json --output=orphan-keys.json
  artifacts:
    paths:
      - missing-keys.json
      - orphan-keys.json
    expire_in: 1 hour
  only:
    - main
    - develop

auto-translate:
  stage: auto-translate
  image: php:8.1
  services:
    - mysql:8.0
  dependencies:
    - detect-new-translations
  before_script:
    - composer install --no-dev --optimize-autoloader
    - php bin/console doctrine:migrations:migrate --no-interaction
  script:
    - |
      if [ -s missing-keys.json ]; then
        echo "Nouvelles clés détectées, lancement de la traduction automatique..."
        
        # Traduire vers toutes les locales supportées
        for locale in fr en es de it; do
          if [ "$locale" != "fr" ]; then  # fr est la langue source
            echo "Traduction automatique vers $locale..."
            php bin/console dahovitech:translation:auto-translate \
              --source=fr \
              --target=$locale \
              --provider=deepl \
              --domain=messages
          fi
        done
        
        # Créer une merge request avec les nouvelles traductions
        git config --global user.email "ci@example.com"
        git config --global user.name "CI Bot"
        git checkout -b "auto-translations-$(date +%Y%m%d-%H%M%S)"
        
        # Exporter les nouvelles traductions
        for locale in fr en es de it; do
          php bin/console dahovitech:translation:export \
            --locale=$locale \
            --format=yaml \
            --output=translations/messages.$locale.yaml
        done
        
        git add translations/
        git commit -m "Auto-translation: nouvelles clés traduites automatiquement"
        git push origin HEAD
        
        # Créer une merge request (nécessite GitLab API token)
        curl --request POST \
          --header "PRIVATE-TOKEN: $GITLAB_API_TOKEN" \
          --data "source_branch=$(git branch --show-current)&target_branch=main&title=Auto-translations $(date +%Y-%m-%d)" \
          "$CI_API_V4_URL/projects/$CI_PROJECT_ID/merge_requests"
      else
        echo "Aucune nouvelle clé détectée."
      fi
  only:
    - main
    - develop

deploy:
  stage: deploy
  image: php:8.1
  before_script:
    - composer install --no-dev --optimize-autoloader
  script:
    - php bin/console cache:clear --env=prod
    - php bin/console dahovitech:translation:cache:clear
    - php bin/console dahovitech:translation:cache:preload --locale=fr
    - php bin/console dahovitech:translation:cache:preload --locale=en
    # Déploiement vers la production
  only:
    - main
```

#### Script de Monitoring Post-Déploiement
```bash
#!/bin/bash
# scripts/monitor-translations.sh

# Configuration
API_BASE_URL="https://api.example.com"
SLACK_WEBHOOK_URL="https://hooks.slack.com/services/..."
COVERAGE_THRESHOLD=90

# Fonction pour envoyer des notifications Slack
send_slack_notification() {
    local message="$1"
    local color="$2"
    
    curl -X POST -H 'Content-type: application/json' \
        --data "{\"attachments\":[{\"color\":\"$color\",\"text\":\"$message\"}]}" \
        "$SLACK_WEBHOOK_URL"
}

# Vérifier la couverture des traductions
echo "Vérification de la couverture des traductions..."

for locale in fr en es de it; do
    # Obtenir les statistiques via l'API
    stats=$(curl -s "$API_BASE_URL/api/v1/translations/stats?locale=$locale")
    coverage=$(echo "$stats" | jq -r '.coverage_percentage')
    
    if (( $(echo "$coverage < $COVERAGE_THRESHOLD" | bc -l) )); then
        message="⚠️ Couverture des traductions faible pour $locale: $coverage% (seuil: $COVERAGE_THRESHOLD%)"
        send_slack_notification "$message" "warning"
        echo "ALERTE: $message"
    else
        echo "✅ Couverture OK pour $locale: $coverage%"
    fi
done

# Vérifier les performances du cache
echo "Vérification des performances du cache..."

cache_stats=$(curl -s "$API_BASE_URL/api/v1/translations/cache/stats")
hit_rate=$(echo "$cache_stats" | jq -r '.hit_rate')

if (( $(echo "$hit_rate < 0.8" | bc -l) )); then
    message="⚠️ Taux de hit du cache faible: $hit_rate (recommandé: >80%)"
    send_slack_notification "$message" "warning"
    echo "ALERTE: $message"
else
    echo "✅ Performance du cache OK: $hit_rate"
fi

# Détecter les nouvelles clés manquantes
echo "Détection des clés manquantes..."

missing_keys=$(php bin/console dahovitech:translation:detect-keys --missing-only --format=json)
missing_count=$(echo "$missing_keys" | jq '.comparison.missing_keys_count // 0')

if [ "$missing_count" -gt 0 ]; then
    message="🔍 $missing_count nouvelles clés de traduction détectées"
    send_slack_notification "$message" "good"
    echo "INFO: $message"
fi

echo "Monitoring terminé."
```

---

## Dépannage

### Problèmes Courants et Solutions

Cette section couvre les problèmes les plus fréquemment rencontrés lors de l'utilisation du bundle et leurs solutions.

### Problèmes de Cache

#### Symptôme : Le cache ne fonctionne pas
**Description :** Les traductions ne sont pas mises en cache ou le cache semble inactif.

**Diagnostic :**
```bash
# Vérifier le statut du cache
php bin/console dahovitech:translation:cache:stats

# Vérifier la configuration du cache
php bin/console debug:config dahovitech_translator
```

**Solutions :**

1. **Vérifier la configuration de l'adaptateur de cache :**
```yaml
# config/packages/cache.yaml
framework:
    cache:
        pools:
            cache.translator:
                adapter: cache.adapter.filesystem  # ou redis, memcached
                default_lifetime: 3600
```

2. **Vérifier les permissions de fichiers :**
```bash
# Donner les bonnes permissions au répertoire de cache
chmod -R 755 var/cache/
chown -R www-data:www-data var/cache/
```

3. **Forcer la régénération du cache :**
```bash
php bin/console cache:clear
php bin/console dahovitech:translation:cache:clear
php bin/console dahovitech:translation:cache:preload --locale=fr
```

#### Symptôme : Erreurs de sérialisation du cache
**Description :** Erreurs lors de la mise en cache des traductions.

**Solution :**
```php
// Vérifier que les objets sont sérialisables
$cacheManager = $container->get('dahovitech_translator.cache_manager');
try {
    $cacheManager->cacheTranslation('test.key', 'fr', 'messages', 'test content');
} catch (\Exception $e) {
    // Analyser l'erreur de sérialisation
    error_log('Cache serialization error: ' . $e->getMessage());
}
```

### Problèmes de Base de Données

#### Symptôme : Erreurs de contraintes de clés étrangères
**Description :** Erreurs lors de l'insertion ou de la mise à jour des traductions.

**Diagnostic :**
```sql
-- Vérifier l'intégrité des tables
SHOW CREATE TABLE dahovitech_translations;
SHOW CREATE TABLE dahovitech_translation_versions;

-- Vérifier les contraintes
SELECT * FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME IN ('dahovitech_translations', 'dahovitech_translation_versions');
```

**Solutions :**

1. **Recréer les migrations :**
```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

2. **Réparer les données corrompues :**
```sql
-- Supprimer les versions orphelines
DELETE tv FROM dahovitech_translation_versions tv
LEFT JOIN dahovitech_translations t ON (
    tv.translation_key = t.translation_key 
    AND tv.locale = t.locale 
    AND tv.domain = t.domain
)
WHERE t.id IS NULL;
```

#### Symptôme : Performances lentes de la base de données
**Description :** Les requêtes de traduction sont lentes.

**Diagnostic :**
```sql
-- Analyser les requêtes lentes
SHOW PROCESSLIST;

-- Vérifier les index
SHOW INDEX FROM dahovitech_translations;
SHOW INDEX FROM dahovitech_translation_versions;
```

**Solutions :**

1. **Optimiser les index :**
```sql
-- Ajouter des index manquants
CREATE INDEX idx_translation_key_locale ON dahovitech_translations(translation_key, locale);
CREATE INDEX idx_version_created_at ON dahovitech_translation_versions(created_at);
```

2. **Nettoyer les anciennes versions :**
```bash
php bin/console dahovitech:translation:cleanup-versions --keep=20
```

### Problèmes de Traduction Automatique

#### Symptôme : Erreurs d'API de traduction
**Description :** Les services de traduction automatique retournent des erreurs.

**Diagnostic :**
```bash
# Tester les providers
php bin/console dahovitech:translation:test-providers

# Vérifier les logs
tail -f var/log/dev.log | grep "auto.translation"
```

**Solutions :**

1. **Vérifier les clés API :**
```bash
# Vérifier que les variables d'environnement sont définies
echo $GOOGLE_TRANSLATE_API_KEY
echo $DEEPL_API_KEY
```

2. **Tester manuellement les APIs :**
```bash
# Test Google Translate API
curl "https://translation.googleapis.com/language/translate/v2?key=$GOOGLE_TRANSLATE_API_KEY" \
  -d "q=hello&target=fr"

# Test DeepL API
curl -X POST "https://api-free.deepl.com/v2/translate" \
  -d "auth_key=$DEEPL_API_KEY" \
  -d "text=hello" \
  -d "target_lang=FR"
```

3. **Configurer des fallbacks :**
```yaml
dahovitech_translator:
    auto_translation:
        providers:
            google:
                api_key: '%env(GOOGLE_TRANSLATE_API_KEY)%'
            deepl:
                api_key: '%env(DEEPL_API_KEY)%'
            libre:
                url: 'https://libretranslate.com'
        fallback_chain: ['deepl', 'google', 'libre']
```

#### Symptôme : Traductions de mauvaise qualité
**Description :** Les traductions automatiques sont incorrectes ou inappropriées.

**Solutions :**

1. **Améliorer la validation :**
```php
// Configurer des règles de validation personnalisées
$validation = $translationManager->validateAutoTranslation(
    $originalText,
    $translatedText,
    $sourceLocale,
    $targetLocale
);

if (!$validation['valid']) {
    // Rejeter la traduction automatique
    // Demander une traduction manuelle
}
```

2. **Utiliser des glossaires personnalisés :**
```yaml
dahovitech_translator:
    auto_translation:
        providers:
            deepl:
                glossary_id: 'your-glossary-id'
                formality: 'more'  # ou 'less'
```

### Problèmes d'Interface d'Administration

#### Symptôme : Interface d'administration inaccessible
**Description :** Erreur 404 ou 403 lors de l'accès à l'interface.

**Diagnostic :**
```bash
# Vérifier les routes
php bin/console debug:router | grep dahovitech

# Vérifier la configuration
php bin/console debug:config dahovitech_translator
```

**Solutions :**

1. **Vérifier la configuration des routes :**
```yaml
# config/routes.yaml
dahovitech_translator_admin:
    resource: '@DahovitechTranslatorBundle/Resources/config/routes.yaml'
    prefix: /admin
```

2. **Vérifier les permissions :**
```php
// src/Security/Voter/TranslationVoter.php
class TranslationVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['TRANSLATION_VIEW', 'TRANSLATION_EDIT']);
    }
    
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof UserInterface) {
            return false;
        }
        
        return match ($attribute) {
            'TRANSLATION_VIEW' => $this->canView($user),
            'TRANSLATION_EDIT' => $this->canEdit($user),
            default => false
        };
    }
}
```

#### Symptôme : Erreurs JavaScript dans l'interface
**Description :** Fonctionnalités JavaScript non fonctionnelles.

**Solutions :**

1. **Vérifier les dépendances :**
```html
<!-- Vérifier que Bootstrap et jQuery sont chargés -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

2. **Déboguer les erreurs JavaScript :**
```javascript
// Activer le mode debug
console.log('Translation admin interface loaded');

// Vérifier les appels AJAX
$(document).ajaxError(function(event, xhr, settings, error) {
    console.error('AJAX Error:', error, xhr.responseText);
});
```

### Problèmes de Détection de Clés

#### Symptôme : Clés non détectées
**Description :** La détection automatique ne trouve pas certaines clés.

**Diagnostic :**
```bash
# Tester la détection sur un fichier spécifique
php bin/console dahovitech:translation:detect-keys src/Controller/UserController.php

# Vérifier les patterns de détection
php bin/console debug:config dahovitech_translator key_detection
```

**Solutions :**

1. **Ajouter des patterns personnalisés :**
```yaml
dahovitech_translator:
    key_detection:
        custom_patterns:
            php:
                - '/customTranslate\([\'"]([^\'"\)]+)[\'"]\)/'
            twig:
                - '/\{\{\s*[\'"]([^\'"\}]+)[\'"]\s*\|\s*myTransFilter\s*\}\}/'
```

2. **Exclure les faux positifs :**
```yaml
dahovitech_translator:
    key_detection:
        exclude_patterns:
            - '/\$\w+/'  # Variables
            - '/^https?:\/\//'  # URLs
            - '/^\d+$/'  # Nombres
```

#### Symptôme : Trop de faux positifs
**Description :** La détection trouve des clés qui ne sont pas des traductions.

**Solutions :**

1. **Affiner les patterns d'exclusion :**
```php
// Ajouter des patterns d'exclusion plus spécifiques
$keyDetector->addExcludePattern('/^[A-Z_]+$/');  // Constantes
$keyDetector->addExcludePattern('/^\w+\.\w+$/');  // Classes/méthodes
```

2. **Utiliser la validation manuelle :**
```bash
# Générer un rapport pour validation manuelle
php bin/console dahovitech:translation:detect-keys --format=csv --output=detected-keys.csv
```

### Problèmes de Performance

#### Symptôme : Lenteur générale du système
**Description :** Les opérations de traduction sont lentes.

**Diagnostic :**
```bash
# Profiler les performances
php bin/console debug:container --show-private | grep dahovitech

# Analyser les requêtes SQL
php bin/console doctrine:query:sql "SHOW PROCESSLIST"
```

**Solutions :**

1. **Optimiser le cache :**
```yaml
framework:
    cache:
        pools:
            cache.translator:
                adapter: cache.adapter.redis
                provider: redis://localhost:6379
```

2. **Optimiser les requêtes :**
```php
// Utiliser des requêtes en lot
$translations = $translationManager->exportTranslations('fr', 'messages');
// Au lieu de multiples appels individuels
```

3. **Précharger le cache :**
```bash
# Précharger lors du déploiement
php bin/console dahovitech:translation:cache:preload --locale=fr
```

### Outils de Diagnostic

#### Script de Diagnostic Complet
```bash
#!/bin/bash
# scripts/diagnose.sh

echo "=== Diagnostic DahovitechTranslatorBundle ==="

echo "1. Vérification de la configuration..."
php bin/console debug:config dahovitech_translator

echo "2. Vérification des services..."
php bin/console debug:container dahovitech_translator --show-private

echo "3. Vérification de la base de données..."
php bin/console doctrine:schema:validate

echo "4. Vérification du cache..."
php bin/console dahovitech:translation:cache:stats

echo "5. Test des providers de traduction..."
php bin/console dahovitech:translation:test-providers

echo "6. Vérification des permissions..."
ls -la var/cache/ var/log/

echo "7. Vérification des routes..."
php bin/console debug:router | grep dahovitech

echo "8. Test de détection de clés..."
php bin/console dahovitech:translation:detect-keys --missing-only | head -10

echo "=== Fin du diagnostic ==="
```

#### Monitoring en Temps Réel
```php
// src/EventListener/TranslationMonitoringListener.php
class TranslationMonitoringListener
{
    public function __construct(
        private LoggerInterface $logger,
        private MetricsCollector $metrics
    ) {
    }
    
    public function onTranslationAccess(TranslationAccessEvent $event): void
    {
        $this->metrics->increment('translation.access', [
            'locale' => $event->getLocale(),
            'domain' => $event->getDomain(),
            'cache_hit' => $event->isCacheHit()
        ]);
        
        if (!$event->isCacheHit()) {
            $this->logger->info('Translation cache miss', [
                'key' => $event->getKey(),
                'locale' => $event->getLocale()
            ]);
        }
    }
    
    public function onTranslationError(TranslationErrorEvent $event): void
    {
        $this->logger->error('Translation error', [
            'key' => $event->getKey(),
            'locale' => $event->getLocale(),
            'error' => $event->getError()->getMessage()
        ]);
        
        $this->metrics->increment('translation.error', [
            'type' => get_class($event->getError())
        ]);
    }
}
```

Cette documentation complète couvre tous les aspects du DahovitechTranslatorBundle version 2.0, depuis l'installation jusqu'au dépannage avancé. Elle fournit des exemples concrets et des solutions pratiques pour une utilisation optimale du bundle dans différents contextes d'application.

