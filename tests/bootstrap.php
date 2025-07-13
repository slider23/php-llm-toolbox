<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env');
    $dotenv->load();
    // Принудительно загружаем переменные в getenv()
    foreach ($_ENV as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}
