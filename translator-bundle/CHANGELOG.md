# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-07-30

### Ajouté
- Bundle Symfony 7.3 pour la gestion des traductions
- Entité Translation avec stockage en base de données
- Service TranslationManager pour la gestion des traductions
- API REST complète avec endpoints CRUD
- Repository TranslationRepository avec méthodes optimisées
- Configuration flexible via DependencyInjection
- Support des domaines de traduction multiples
- Fonctionnalités d'import/export des traductions
- Détection des traductions manquantes
- Tests unitaires complets
- Documentation complète avec exemples d'utilisation
- Support de Symfony 7.3 et PHP 8.1+

### Fonctionnalités principales
- Création, lecture, mise à jour et suppression de traductions
- Gestion multi-locales et multi-domaines
- API REST pour intégration externe
- Import/export en lot
- Recherche de traductions manquantes
- Intégration native avec le système de traduction Symfony
- Configuration avancée avec cache et options personnalisables

### Endpoints API
- `GET /api/translations` - Liste des traductions
- `GET /api/translations/{key}` - Récupération d'une traduction
- `POST /api/translations/{key}` - Création/mise à jour d'une traduction
- `DELETE /api/translations/{key}` - Suppression d'une traduction
- `POST /api/translations/import` - Import en lot
- `GET /api/translations/export` - Export des traductions
- `GET /api/translations/locales` - Liste des locales
- `GET /api/translations/keys` - Liste des clés
- `GET /api/translations/missing` - Traductions manquantes

