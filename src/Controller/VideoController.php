<?php

namespace App\Controller;

use App\Service\VideoService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoController
{
    private $videoService;

    // Конструктор для получения зависимости VideoService через DI
    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    // Метод для обработки загрузки видео
    public function uploadVideo(ServerRequestInterface $request, ResponseInterface $response)
    {
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['video'])) {
            $response->getBody()->write("No video uploaded.");
            return $response->withStatus(400);
        }

        $uploadedFile = $uploadedFiles['video'];

        try {
            // Обрабатываем видео (загружаем и конвертируем)
            $videoUrl = $this->videoService->processVideo($uploadedFile);
            $response->getBody()->write("Video uploaded and converted successfully: " . $videoUrl);
            return $response->withStatus(200);
        } catch (\RuntimeException $e) {
            $response->getBody()->write("Error processing video: " . $e->getMessage());
            return $response->withStatus(500);
        }
    }
}
