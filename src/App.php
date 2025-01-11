<?php

namespace App;

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use App\Config\ContainerConfig;

class App
{
    public static function run()
    {
        static::create()->run();
    }

    public static function create(): \Slim\App
    {
        // Настройка DI контейнера через отдельный конфигурационный файл
        $containerBuilder = new ContainerBuilder();
        $container = $containerBuilder->build();
        ContainerConfig::configure($container); // Вызываем конфигурацию

        // Создание приложения Slim
        AppFactory::setContainer($container);
        return AppFactory::create();
    }
}
