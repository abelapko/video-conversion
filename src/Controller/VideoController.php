<?php

namespace App\Controller;

use App\Cloud\YandexCloudStorageService;
use App\Service\VideoService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class VideoController
{
    private $videoService;
    private $cloudStorageService;

    public function __construct(VideoService $videoService, YandexCloudStorageService $cloudStorageService)
    {
        $this->videoService = $videoService;
        $this->cloudStorageService = $cloudStorageService;
    }

    public function uploadVideo(ServerRequestInterface $request, ResponseInterface $response)
    {
        $uploadedFiles = $request->getUploadedFiles();
        if (empty($uploadedFiles['video'])) {
            $response->getBody()->write(json_encode(['error' => 'No video uploaded']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $uploadedFile = $uploadedFiles['video'];

        try {
            // Обрабатываем видео и получаем taskId
            $taskId = $this->videoService->processVideo($uploadedFile);

            // Формируем JSON-ответ
            $response->getBody()->write(json_encode([
                'message' => 'Video uploaded successfully',
                'taskId' => $taskId,
            ]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\RuntimeException $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function checkTaskStatus(ServerRequestInterface $request, ResponseInterface $response)
    {
        $taskId = $request->getAttribute('taskId');

        try {
            $status = $this->videoService->getTaskStatus($taskId);
            if ($status === null) {
                $response->getBody()->write(json_encode(['error' => 'Task not found']));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode(['taskId' => $taskId, 'status' => $status]));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Метод для скачивания видео
    public function downloadVideo(ServerRequestInterface $request, ResponseInterface $response, $args)
    {
        $taskId = $args['taskId']; // Получаем taskId из URL
        $filename = 'converted_videos/' . $taskId . '.mp4'; // Путь к файлу в облаке

        try {
            // Скачиваем файл с облака
            $fileContent = $this->cloudStorageService->downloadFile($filename);

            if ($fileContent === null) {
                return $response->withStatus(404);
            }

            // Отправляем файл в ответ
            $response->getBody()->write($fileContent);
            return $response->withHeader('Content-Type', 'video/mp4')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $taskId . '.mp4"')
                ->withStatus(200);

        } catch (\Exception $e) {
            return $response->withStatus(404);
        }
    }
}
