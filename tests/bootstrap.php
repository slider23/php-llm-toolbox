<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    // Читаем .env файл напрямую для гарантии загрузки всех переменных
    $envFile = dirname(__DIR__) . '/.env';
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    // Также используем dotenv как fallback
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env');
    $dotenv->safeLoad();
}
