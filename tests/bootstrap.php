<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Загружаем .env файл
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

putenv('APP_ENV=test');
// sync env
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}