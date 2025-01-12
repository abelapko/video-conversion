<?php

namespace App\Service;

use App\Cloud\YandexCloudStorageService;
use App\Repository\RabbitMQRepository;
use App\Repository\RedisRepository;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class VideoService
{
    private $cloudStorageService;
    private $rabbitMQRepository;
    private $redisRepository;
    private $uploadPath;
    private $logger;

    public function __construct(
        YandexCloudStorageService $cloudStorageService,
        RabbitMQRepository $rabbitMQRepository,
        RedisRepository $redisRepository,
        string $uploadPath,
        LoggerInterface $logger
    ) {
        $this->cloudStorageService = $cloudStorageService;
        $this->rabbitMQRepository = $rabbitMQRepository;
        $this->redisRepository = $redisRepository;
        $this->uploadPath = $uploadPath;
        $this->logger = $logger;
    }

    public function processVideo(UploadedFileInterface $file): string
    {
        $uploadedFilePath = $this->uploadPath . DIRECTORY_SEPARATOR . $file->getClientFilename();
        $this->logger->info('Starting video upload process', ['file' => $file->getClientFilename()]);

        try {
            // Сохраняем файл в локальное хранилище
            $file->moveTo($uploadedFilePath);
            $this->logger->info('File moved to upload path', ['path' => $uploadedFilePath]);
        } catch (\Exception $e) {
            $this->logger->error('Error moving file', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Error saving uploaded file: ' . $e->getMessage());
        }

        try {
            // Загружаем файл в облако
            $this->logger->info('Uploading video to cloud storage');
            $cloudUrl = $this->cloudStorageService->uploadFile($uploadedFilePath, $file->getClientFilename());

            // Генерация уникального идентификатора задачи
            $taskId = Uuid::uuid4()->toString();

            // Сохраняем статус задачи в Redis как "in_progress"
            $this->redisRepository->setTaskStatus($taskId, 'in_progress');

            // Постановка задачи на конвертацию в очередь
            $this->logger->info('Publishing task to conversion queue', ['taskId' => $taskId]);
            $this->rabbitMQRepository->sendToQueue([
                'taskId' => $taskId,
                'sourceUrl' => $cloudUrl,
                'format' => 'avi',
            ]);

            $this->logger->info('Task published successfully', ['taskId' => $taskId]);

            return $taskId;
        } catch (\Exception $e) {
            $this->logger->error('Error processing video', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Error processing video: ' . $e->getMessage());
        }
    }

    public function updateTaskStatus(string $taskId, string $status)
    {
        // Обновляем статус задачи в Redis
        $this->redisRepository->setTaskStatus($taskId, $status);
    }

    /**
     * Получить статус задачи из Redis.
     *
     * @param string $taskId Уникальный идентификатор задачи.
     * @return string|null Статус задачи или null, если задача не найдена.
     */
    public function getTaskStatus(string $taskId): ?string
    {
        // Запрашиваем статус задачи из Redis
        return $this->redisRepository->getTaskStatus($taskId);
    }
}
