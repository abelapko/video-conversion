<?php

namespace App\Service;

use App\Cloud\YandexCloudStorageService;
use App\Converter\VideoConverter;
use Psr\Http\Message\UploadedFileInterface;

class VideoService
{
    private $cloudStorageService;
    private $videoConverter;
    private $uploadPath;
    private $convertPath;

    public function __construct(YandexCloudStorageService $cloudStorageService, VideoConverter $videoConverter, $uploadPath, $convertPath)
    {
        $this->cloudStorageService = $cloudStorageService;
        $this->videoConverter = $videoConverter;
        $this->uploadPath = $uploadPath;
        $this->convertPath = $convertPath;
    }

    public function processVideo(UploadedFileInterface $file)
    {
        // Сохраняем файл в временную папку
        $uploadedFilePath = $this->uploadPath . DIRECTORY_SEPARATOR . $file->getClientFilename();
        $file->moveTo($uploadedFilePath);

        try {
            // Загружаем исходное видео в Яндекс Object Storage
            $this->cloudStorageService->uploadFile($uploadedFilePath, 'videos/' . $file->getClientFilename());

            // Конвертируем видео
            $convertedFilePath = $this->convertPath . DIRECTORY_SEPARATOR . pathinfo($uploadedFilePath, PATHINFO_FILENAME) . '.avi';
            $this->videoConverter->convert($uploadedFilePath, $convertedFilePath);

            // Загружаем конвертированное видео в Яндекс Object Storage
            $this->cloudStorageService->uploadFile($convertedFilePath, 'converted/' . pathinfo($uploadedFilePath, PATHINFO_FILENAME) . '.avi');

            // Возвращаем URL конвертированного файла
            return 'https://' . $this->cloudStorageService->getBucket() . '.storage.yandexcloud.net/converted/' . pathinfo($uploadedFilePath, PATHINFO_FILENAME) . '.avi';

        } catch (\Exception $e) {
            throw new \RuntimeException('Error processing video: ' . $e->getMessage());
        }
    }
}
