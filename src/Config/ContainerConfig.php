<?php

namespace App\Config;

use App\Repository\RabbitMQRepository;
use DI\Container;
use App\Cloud\YandexCloudStorageService;
use App\Service\VideoService;
use App\Converter\VideoConverter;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

class ContainerConfig
{
    public static function configure(Container $container)
    {
        // Получаем конфигурацию
        $config = require __DIR__ . '/../Config/parameters.php';

        // Регистрируем AMQPStreamConnection в контейнере
        $container->set(AMQPStreamConnection::class, function () use ($config) {
            return new AMQPStreamConnection(
                getenv('RABBITMQ_HOST'),
                getenv('RABBITMQ_PORT'),
                getenv('RABBITMQ_USER'),
                getenv('RABBITMQ_PASSWORD')
            );
        });

        // Регистрируем RabbitMQRepository с зависимостью от AMQPStreamConnection
        $container->set(RabbitMQRepository::class, function ($container) {
            return new RabbitMQRepository($container->get(AMQPStreamConnection::class));
        });

        // Регистрация сервисов в контейнере
        $container->set(VideoConverter::class, function () {
            return new VideoConverter();
        });

        $container->set(YandexCloudStorageService::class, function () use ($config) {
            return new YandexCloudStorageService($config['yandex']);
        });

        $container->set(LoggerInterface::class, function () {
            $logDirectory = __DIR__ . '/../logs';

            // Проверяем, существует ли папка, и создаём её, если она отсутствует
            if (!is_dir($logDirectory)) {
                if (!mkdir($logDirectory, 0777, true) && !is_dir($logDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDirectory));
                }
            }

            // Настраиваем логгер
            $logger = new Logger('app');
            $logger->pushHandler(new StreamHandler($logDirectory . '/app.log', Logger::DEBUG));

            return $logger;
        });

        $container->set(VideoService::class, function () use ($container, $config) {
            return new VideoService(
                $container->get(YandexCloudStorageService::class),
                $container->get(VideoConverter::class),
                $config['upload_path'],
                $config['convert_path'],
                $container->get(LoggerInterface::class)
            );
        });
    }
}
