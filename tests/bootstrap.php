<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// Configuration spécifique pour les tests
$_ENV['APP_ENV'] = 'test';
$_SERVER['APP_ENV'] = 'test';

// Configuration de la base de données de test
$_ENV['DATABASE_URL'] = 'sqlite:///:memory:';
$_SERVER['DATABASE_URL'] = 'sqlite:///:memory:';

// Configuration du cache pour les tests
$_ENV['CACHE_ADAPTER'] = 'cache.adapter.array';
$_SERVER['CACHE_ADAPTER'] = 'cache.adapter.array';

// Configuration des providers de traduction pour les tests
$_ENV['GOOGLE_TRANSLATE_API_KEY'] = 'test-google-key';
$_ENV['DEEPL_API_KEY'] = 'test-deepl-key';
$_ENV['LIBRETRANSLATE_API_KEY'] = 'test-libre-key';

$_SERVER['GOOGLE_TRANSLATE_API_KEY'] = 'test-google-key';
$_SERVER['DEEPL_API_KEY'] = 'test-deepl-key';
$_SERVER['LIBRETRANSLATE_API_KEY'] = 'test-libre-key';

