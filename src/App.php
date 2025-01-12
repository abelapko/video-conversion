<?php

namespace App;

use DI\Container;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use App\Config\ContainerConfig;

class App
{
    public static function run()
    {
        static::instance()->run();
    }

    public static function container(): Container
    {
        // Настройка DI контейнера через отдельный конфигурационный файл
        $containerBuilder = new ContainerBuilder();
        $container = $containerBuilder->build();
        ContainerConfig::configure($container); // Вызываем конфигурацию
        return $container;
    }

    public static function instance(): \Slim\App
    {
        $container = static::container();
        // Создание приложения Slim
        AppFactory::setContainer($container);
        return AppFactory::create();
    }
}
