<?php

namespace App\Config;

use Psr\Container\ContainerInterface;
use Slim\Container;
use App\Cloud\YandexCloudStorageService;
use App\Service\VideoService;
use App\Converter\VideoConverter;

class ContainerConfig
{
    public static function configure(ContainerInterface $container)
    {
        // Получаем конфигурацию
        $config = require __DIR__ . '/../Config/parameters.php';

        // Регистрация сервисов в контейнере
        $container[VideoConverter::class] = function () {
            return new VideoConverter();
        };

        $container[YandexCloudStorageService::class] = function () use ($config) {
            return new YandexCloudStorageService($config['yandex']);
        };

        $container[VideoService::class] = function () use ($container, $config) {
            return new VideoService(
                $container->get(YandexCloudStorageService::class),
                $container->get(VideoConverter::class),
                $config['upload_path'],
                $config['convert_path']
            );
        };
    }
}
