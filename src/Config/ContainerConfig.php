<?php

namespace App\Config;

use DI\Container;
use App\Cloud\YandexCloudStorageService;
use App\Service\VideoService;
use App\Converter\VideoConverter;

class ContainerConfig
{
    public static function configure(Container $container)
    {
        // Получаем конфигурацию
        $config = require __DIR__ . '/../Config/parameters.php';

        // Регистрация сервисов в контейнере
        $container->set(VideoConverter::class, function () {
            return new VideoConverter();
        });

        $container->set(YandexCloudStorageService::class, function () use ($config) {
            return new YandexCloudStorageService($config['yandex']);
        });

        $container->set(VideoService::class, function () use ($container, $config) {
            return new VideoService(
                $container->get(YandexCloudStorageService::class),
                $container->get(VideoConverter::class),
                $config['upload_path'],
                $config['convert_path']
            );
        });
    }
}
