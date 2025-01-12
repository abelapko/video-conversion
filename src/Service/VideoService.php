<?php

namespace App\Service;

use App\Cloud\YandexCloudStorageService;
use App\Converter\VideoConverter;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;

class VideoService
{
    private $cloudStorageService;
    private $videoConverter;
    private $uploadPath;
    private $convertPath;
    private $logger;

    public function __construct(
        YandexCloudStorageService $cloudStorageService,
        VideoConverter $videoConverter,
                                  $uploadPath,
                                  $convertPath,
        LoggerInterface $logger
    ) {
        $this->cloudStorageService = $cloudStorageService;
        $this->videoConverter = $videoConverter;
        $this->uploadPath = $uploadPath;
        $this->convertPath = $convertPath;
        $this->logger = $logger;
    }

    public function processVideo(UploadedFileInterface $file)
    {
        $uploadedFilePath = $this->uploadPath . DIRECTORY_SEPARATOR . $file->getClientFilename();
        $this->logger->info('Starting video processing', ['file' => $file->getClientFilename()]);

        // Сохраняем файл в временную папку
        try {
            $file->moveTo($uploadedFilePath);
            $this->logger->info('File moved to upload path', ['path' => $uploadedFilePath]);
        } catch (\Exception $e) {
            $this->logger->error('Error moving file', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Error saving uploaded file: ' . $e->getMessage());
        }

        try {
            // Загружаем исходное видео в Яндекс Object Storage
            $this->logger->info('Uploading original video to cloud storage');
            $this->cloudStorageService->uploadFile($uploadedFilePath, 'videos/' . $file->getClientFilename());

            // Конвертируем видео
            $convertedFilePath = $this->convertPath . DIRECTORY_SEPARATOR . pathinfo($uploadedFilePath, PATHINFO_FILENAME) . '.avi';
            $this->logger->info('Converting video', [
                'input' => $uploadedFilePath,
                'output' => $convertedFilePath,
            ]);
            $this->videoConverter->convert($uploadedFilePath, $convertedFilePath);

            // Загружаем конвертированное видео в Яндекс Object Storage
            $this->logger->info('Uploading converted video to cloud storage');
            $this->cloudStorageService->uploadFile($convertedFilePath, 'converted/' . pathinfo($uploadedFilePath, PATHINFO_FILENAME) . '.avi');

            // Возвращаем URL конвертированного файла
            $url = 'https://storage.yandexcloud.net/converted/' . pathinfo($uploadedFilePath, PATHINFO_FILENAME) . '.avi';
            $this->logger->info('Video processing completed successfully', ['url' => $url]);

            return $url;

        } catch (\Exception $e) {
            $this->logger->error('Error during video processing', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Error processing video: ' . $e->getMessage());
        }
    }
}
