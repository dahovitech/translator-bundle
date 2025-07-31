<?php

namespace Dahovitech\TranslatorBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('dahovitech_translator');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('locales')
                    ->info('Liste des locales supportées')
                    ->scalarPrototype()->end()
                    ->defaultValue(['en', 'fr'])
                ->end()
                ->scalarNode('default_locale')
                    ->info('Locale par défaut')
                    ->defaultValue('en')
                ->end()
                ->scalarNode('fallback_locale')
                    ->info('Locale de fallback')
                    ->defaultValue('en')
                ->end()
                ->arrayNode('domains')
                    ->info('Domaines de traduction disponibles')
                    ->scalarPrototype()->end()
                    ->defaultValue(['messages', 'validators', 'security'])
                ->end()
                ->booleanNode('enable_api')
                    ->info('Active l\'API REST pour les traductions')
                    ->defaultTrue()
                ->end()
                ->scalarNode('api_prefix')
                    ->info('Préfixe pour les routes de l\'API')
                    ->defaultValue('/api/translations')
                ->end()
                ->booleanNode('enable_cache')
                    ->info('Active la mise en cache des traductions')
                    ->defaultTrue()
                ->end()
                ->scalarNode('cache_ttl')
                    ->info('Durée de vie du cache en secondes')
                    ->defaultValue(3600)
                ->end()
                ->booleanNode('auto_create_missing')
                    ->info('Crée automatiquement les traductions manquantes')
                    ->defaultFalse()
                ->end()
                ->arrayNode('import')
                    ->info('Configuration pour l\'import de traductions')
                    ->children()
                        ->arrayNode('sources')
                            ->info('Sources d\'import (fichiers YAML, JSON, etc.)')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                        ->booleanNode('overwrite_existing')
                            ->info('Écrase les traductions existantes lors de l\'import')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('export')
                    ->info('Configuration pour l\'export de traductions')
                    ->children()
                        ->scalarNode('format')
                            ->info('Format d\'export (yaml, json, php)')
                            ->defaultValue('yaml')
                        ->end()
                        ->scalarNode('output_dir')
                            ->info('Répertoire de sortie pour l\'export')
                            ->defaultValue('%kernel.project_dir%/translations')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}

