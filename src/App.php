<?php

namespace App;

use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use App\Config\ContainerConfig;

class App
{
    public static function run()
    {
        // Настройка DI контейнера через отдельный конфигурационный файл
        $containerBuilder = new ContainerBuilder();
        ContainerConfig::configure($containerBuilder); // Вызываем конфигурацию
        $container = $containerBuilder->build();

        // Создание приложения Slim
        AppFactory::setContainer($container);
        $app = AppFactory::create();

        // Запуск приложения
        $app->run();
    }
}
