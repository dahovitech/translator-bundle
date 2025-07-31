# DahovitechTranslatorBundle

Un bundle Symfony 7.3 pour la gestion avancée des traductions de contenu avec stockage en base de données et API REST.

## Fonctionnalités

- **Stockage en base de données** : Toutes les traductions sont stockées en base de données avec Doctrine ORM
- **API REST complète** : Endpoints pour créer, lire, mettre à jour et supprimer les traductions
- **Gestion multi-domaines** : Support des domaines de traduction Symfony
- **Import/Export** : Fonctionnalités d'import et d'export des traductions
- **Détection des traductions manquantes** : Identification automatique des traductions manquantes
- **Configuration flexible** : Configuration complète via les fichiers de configuration Symfony
- **Tests unitaires** : Suite de tests complète pour assurer la qualité du code

## Installation

### 1. Installation via Composer

```bash
composer require dahovitech/translator-bundle
```

### 2. Enregistrement du bundle

Si vous n'utilisez pas Symfony Flex, ajoutez le bundle dans `config/bundles.php` :

```php
<?php

return [
    // ...
    Dahovitech\TranslatorBundle\DahovitechTranslatorBundle::class => ['all' => true],
];
```

### 3. Configuration

Créez le fichier de configuration `config/packages/dahovitech_translator.yaml` :

```yaml
dahovitech_translator:
    locales: ['en', 'fr', 'es']
    default_locale: 'en'
    fallback_locale: 'en'
    domains: ['messages', 'validators', 'security']
    enable_api: true
    api_prefix: '/api/translations'
    enable_cache: true
    cache_ttl: 3600
    auto_create_missing: false
    import:
        sources: []
        overwrite_existing: false
    export:
        format: 'yaml'
        output_dir: '%kernel.project_dir%/translations'
```

### 4. Mise à jour de la base de données

Créez et exécutez la migration pour créer la table des traductions :

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 5. Configuration des routes (optionnel)

Si vous souhaitez personnaliser les routes de l'API, ajoutez dans `config/routes.yaml` :

```yaml
dahovitech_translator:
    resource: '@DahovitechTranslatorBundle/Resources/config/routes.yaml'
```

## Utilisation

### Service TranslationManager

Le service principal `TranslationManager` fournit toutes les fonctionnalités de gestion des traductions :

```php
<?php

use Dahovitech\TranslatorBundle\Service\TranslationManager;

class YourController
{
    public function __construct(
        private TranslationManager $translationManager
    ) {
    }

    public function example(): void
    {
        // Créer ou mettre à jour une traduction
        $this->translationManager->setTranslation(
            'welcome.message', 
            'fr', 
            'Bienvenue sur notre site !',
            'messages'
        );

        // Récupérer une traduction
        $translation = $this->translationManager->getTranslation(
            'welcome.message', 
            'fr', 
            'messages'
        );

        // Vérifier si une traduction existe
        $exists = $this->translationManager->hasTranslation(
            'welcome.message', 
            'fr', 
            'messages'
        );

        // Supprimer une traduction
        $deleted = $this->translationManager->removeTranslation(
            'welcome.message', 
            'fr', 
            'messages'
        );

        // Importer des traductions en lot
        $translations = [
            'hello' => 'Bonjour',
            'goodbye' => 'Au revoir',
            'thank_you' => 'Merci'
        ];
        $count = $this->translationManager->importTranslations($translations, 'fr', 'messages');

        // Exporter toutes les traductions d'une locale
        $allTranslations = $this->translationManager->exportTranslations('fr', 'messages');

        // Obtenir les locales disponibles
        $locales = $this->translationManager->getAvailableLocales();

        // Obtenir toutes les clés de traduction
        $keys = $this->translationManager->getTranslationKeys();

        // Trouver les traductions manquantes
        $missing = $this->translationManager->findMissingTranslations('fr', 'en');
    }
}
```

### API REST

Le bundle fournit une API REST complète pour gérer les traductions :

#### Endpoints disponibles

- `GET /api/translations` - Liste toutes les traductions pour une locale
- `GET /api/translations/{key}` - Récupère une traduction spécifique
- `POST /api/translations/{key}` - Crée ou met à jour une traduction
- `DELETE /api/translations/{key}` - Supprime une traduction
- `POST /api/translations/import` - Importe des traductions en lot
- `GET /api/translations/export` - Exporte les traductions
- `GET /api/translations/locales` - Liste les locales disponibles
- `GET /api/translations/keys` - Liste toutes les clés de traduction
- `GET /api/translations/missing` - Trouve les traductions manquantes

#### Exemples d'utilisation de l'API

**Récupérer toutes les traductions pour une locale :**
```bash
curl -X GET "http://your-app.com/api/translations?locale=fr&domain=messages"
```

**Créer une nouvelle traduction :**
```bash
curl -X POST "http://your-app.com/api/translations/welcome.message" \
  -H "Content-Type: application/json" \
  -d '{
    "locale": "fr",
    "content": "Bienvenue !",
    "domain": "messages"
  }'
```

**Importer des traductions en lot :**
```bash
curl -X POST "http://your-app.com/api/translations/import" \
  -H "Content-Type: application/json" \
  -d '{
    "locale": "fr",
    "domain": "messages",
    "translations": {
      "hello": "Bonjour",
      "goodbye": "Au revoir"
    }
  }'
```

### Intégration avec le système de traduction Symfony

Le bundle s'intègre parfaitement avec le système de traduction Symfony existant. Vous pouvez utiliser les fonctions de traduction habituelles :

```php
// Dans un contrôleur
$this->translator->trans('welcome.message', [], 'messages', 'fr');

// Dans un template Twig
{{ 'welcome.message'|trans({}, 'messages', 'fr') }}
```

## Structure de la base de données

Le bundle crée une table `dahovitech_translations` avec la structure suivante :

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire auto-incrémentée |
| translation_key | VARCHAR(255) | Clé de la traduction |
| locale | VARCHAR(10) | Code de la locale (ex: 'fr', 'en') |
| content | TEXT | Contenu de la traduction |
| domain | VARCHAR(100) | Domaine de traduction |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de dernière modification |

Un index composite est créé sur `(translation_key, locale)` pour optimiser les performances.

## Configuration avancée

### Options de configuration

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `locales` | array | `['en', 'fr']` | Liste des locales supportées |
| `default_locale` | string | `'en'` | Locale par défaut |
| `fallback_locale` | string | `'en'` | Locale de fallback |
| `domains` | array | `['messages', 'validators', 'security']` | Domaines disponibles |
| `enable_api` | boolean | `true` | Active/désactive l'API REST |
| `api_prefix` | string | `'/api/translations'` | Préfixe des routes API |
| `enable_cache` | boolean | `true` | Active la mise en cache |
| `cache_ttl` | integer | `3600` | Durée de vie du cache en secondes |
| `auto_create_missing` | boolean | `false` | Crée automatiquement les traductions manquantes |

### Configuration de l'import

```yaml
dahovitech_translator:
    import:
        sources:
            - '%kernel.project_dir%/translations/messages.fr.yaml'
            - '%kernel.project_dir%/translations/validators.fr.yaml'
        overwrite_existing: true
```

### Configuration de l'export

```yaml
dahovitech_translator:
    export:
        format: 'json'  # yaml, json, php
        output_dir: '%kernel.project_dir%/var/translations'
```

## Tests

Le bundle inclut une suite de tests unitaires complète. Pour exécuter les tests :

```bash
composer install --dev
vendor/bin/phpunit
```

## Contribution

Les contributions sont les bienvenues ! Veuillez suivre ces étapes :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/amazing-feature`)
3. Committez vos changements (`git commit -m 'Add amazing feature'`)
4. Poussez vers la branche (`git push origin feature/amazing-feature`)
5. Ouvrez une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur GitHub.

