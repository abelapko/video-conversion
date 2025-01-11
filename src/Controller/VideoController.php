<?php

namespace App\Controller;

use App\Service\VideoService;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\StreamFactory;

class VideoController
{
    private $videoService;

    public function __construct(VideoService $videoService)
    {
        $this->videoService = $videoService;
    }

    // Маршрут для загрузки видео
    public function uploadVideo(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();

        // Проверяем, был ли загружен файл
        if (empty($uploadedFiles['video'])) {
            $response->getBody()->write(json_encode(['message' => 'No video file uploaded.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $videoFile = $uploadedFiles['video'];

        // Проверяем тип файла
        if ($videoFile->getClientMediaType() !== 'video/mp4') {
            $response->getBody()->write(json_encode(['message' => 'File is not a valid MP4 video.']));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Получаем путь для сохранения видео
        $uploadPath = '/tmp/uploads/';
        $videoPath = $uploadPath . $videoFile->getClientFilename();

        // Сохраняем файл на сервере
        $videoFile->moveTo($videoPath);

        try {
            // Обрабатываем видео (конвертируем и загружаем в облако)
            $this->videoService->processVideo($videoFile);

            // Возвращаем успешный ответ
            $response->getBody()->write(json_encode(['message' => 'Video uploaded, converted and uploaded to cloud successfully.']));
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['message' => 'Error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    // Маршрут для получения видео
    public function getVideo(RequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $fileName = $args['fileName'];
        $filePath = '/tmp/uploads/' . $fileName;

        if (!file_exists($filePath)) {
            $response->getBody()->write(json_encode(['message' => 'File not found.']));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $stream = (new StreamFactory())->createStreamFromFile($filePath);

        return $response->withHeader('Content-Type', 'video/mp4')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"')
            ->withBody($stream);
    }
}
