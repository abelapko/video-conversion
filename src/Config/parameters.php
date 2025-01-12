<?php

$env = getenv('APP_ENV') ?: 'dev'; // По умолчанию окружение — dev
$parametersFile = __DIR__ . "/parameters.{$env}.php";

if (!file_exists($parametersFile)) {
    throw new \RuntimeException("Configuration file for environment '{$env}' not found.");
}

return require $parametersFile;
