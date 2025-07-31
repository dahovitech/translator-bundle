<?php

namespace Dahovitech\TranslatorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DahovitechTranslatorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Charger les services
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Définir les paramètres de configuration
        $container->setParameter('dahovitech_translator.locales', $config['locales']);
        $container->setParameter('dahovitech_translator.default_locale', $config['default_locale']);
        $container->setParameter('dahovitech_translator.fallback_locale', $config['fallback_locale']);
        $container->setParameter('dahovitech_translator.domains', $config['domains']);
        $container->setParameter('dahovitech_translator.enable_api', $config['enable_api']);
        $container->setParameter('dahovitech_translator.api_prefix', $config['api_prefix']);
        $container->setParameter('dahovitech_translator.enable_cache', $config['enable_cache']);
        $container->setParameter('dahovitech_translator.cache_ttl', $config['cache_ttl']);
        $container->setParameter('dahovitech_translator.auto_create_missing', $config['auto_create_missing']);
        $container->setParameter('dahovitech_translator.import', $config['import']);
        $container->setParameter('dahovitech_translator.export', $config['export']);

        // Configurer les services conditionnellement
        if (!$config['enable_api']) {
            $container->removeDefinition('dahovitech_translator.controller.translation');
        }
    }

    public function getAlias(): string
    {
        return 'dahovitech_translator';
    }
}

