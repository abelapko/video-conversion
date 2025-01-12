<?php

namespace App\Worker;

use PhpAmqpLib\Message\AMQPMessage;
use App\Converter\VideoConverter;
use App\Cloud\YandexCloudStorageService;
use App\Repository\RabbitMQRepository;
use Psr\Log\LoggerInterface;

class VideoConversionWorker
{
    private $rabbitMQRepository;
    private $videoConverter;
    private $cloudStorageService;
    private string $convertPath;
    private $logger;

    public function __construct(
        RabbitMQRepository $rabbitMQRepository,
        VideoConverter $videoConverter,
        YandexCloudStorageService $cloudStorageService,
        string $convertPath,
        LoggerInterface $logger
    ) {
        $this->rabbitMQRepository = $rabbitMQRepository;
        $this->videoConverter = $videoConverter;
        $this->cloudStorageService = $cloudStorageService;
        $this->convertPath = $convertPath;
        $this->logger = $logger;
    }

    public function start()
    {
        // Подключаемся к очереди и начинаем слушать задачи
        $this->rabbitMQRepository->getChannel()->basic_consume(
            'video_conversion_queue', // имя очереди
            '', // имя потребителя (по умолчанию)
            false, // не авто-ак acknowledging
            true, // берем задачи по мере их поступления
            false, // отключаем ограничение на количество сообщений
            false, // отключаем повторное потребление
            [$this, 'processTask'] // обработчик задач
        );

        // Ожидаем задачи
        while ($this->rabbitMQRepository->getChannel()->is_consuming()) {
            $this->rabbitMQRepository->getChannel()->wait();
        }
    }

    // Метод для обработки задачи
    public function processTask(AMQPMessage $msg)
    {
        $taskData = json_decode($msg->getBody(), true);
        $taskId = $taskData['taskId']; // taskId для отслеживания
        $fileUrl = $taskData['fileUrl'];
        $outputFormat = $taskData['outputFormat'] ?? 'avi';

        // Обновляем статус задачи: "Started"
        $this->sendTaskStatusUpdate($taskId, 'started');

        $this->logger->info("Started processing video: {$fileUrl} to {$outputFormat}");

        try {
            // Путь для сохранения сконвертированного видео
            $outputPath = $this->convertPath . DIRECTORY_SEPARATOR . basename($fileUrl) . '.' . $outputFormat;

            // Конвертируем видео
            $this->videoConverter->convert($fileUrl, $outputPath);

            // Загружаем сконвертированное видео в облако
            $this->cloudStorageService->uploadFile($outputPath, 'converted_videos/' . basename($outputPath));

            // Логируем успешную обработку
            $this->logger->info("Successfully processed video: {$fileUrl} to {$outputFormat}");

            // Обновляем статус задачи: "Completed"
            $this->sendTaskStatusUpdate($taskId, 'completed');

        } catch (\Exception $e) {
            // Логируем ошибку в процессе обработки
            $this->logger->error("Error processing video {$fileUrl}: " . $e->getMessage());

            // Обновляем статус задачи: "Failed"
            $this->sendTaskStatusUpdate($taskId, 'failed');
        }
    }

    // Метод для отправки обновлений статуса задачи в очередь
    private function sendTaskStatusUpdate($taskId, $status)
    {
        $routingKey = 'status.' . $taskId; // Используем taskId как часть ключа маршрутизации
        $msg = new AMQPMessage(
            json_encode(['taskId' => $taskId, 'status' => $status]),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );

        // Отправляем сообщение в exchange с указанным routing key
        $this->rabbitMQRepository->getChannel()->basic_publish($msg, 'status_exchange', $routingKey);
    }
}
