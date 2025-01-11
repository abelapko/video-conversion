<?php

namespace App\Tests\Controller;

use App\Controller\VideoController;
use App\Service\VideoService;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\ResponseFactory;

class VideoControllerTest extends TestCase
{
    private $videoService;
    private $videoController;

    protected function setUp(): void
    {
        // Мокируем VideoService
        $this->videoService = $this->createMock(VideoService::class);

        // Создаем контроллер с мокированным сервисом
        $this->videoController = new VideoController($this->videoService);
    }

    public function testUploadVideoSuccess()
    {
        // Мокируем необходимые объекты
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('test_video.mp4');

        $this->videoService
            ->expects($this->once())  // Проверяем, что метод processVideo будет вызван один раз
            ->method('processVideo')
            ->with($uploadedFile)
            ->willReturn('https://storage.yandexcloud.net/converted/test_video.avi');

        // Мокируем request и response
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUploadedFiles')->willReturn(['file' => $uploadedFile]);

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        // Вызываем метод контроллера
        $response = $this->videoController->uploadVideo($request, $response, []);

        // Проверяем статус код и возвращаемый результат
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());
        $this->assertStringContainsString('https://storage.yandexcloud.net/converted/test_video.avi', (string)$response->getBody());
    }

    public function testUploadVideoFailure()
    {
        // Мокируем необходимые объекты
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('test_video.mp4');

        // Мокируем ошибку в сервисе
        $this->videoService
            ->expects($this->once())  // Проверяем, что метод processVideo будет вызван один раз
            ->method('processVideo')
            ->with($uploadedFile)
            ->willThrowException(new \RuntimeException('Error processing video'));

        // Мокируем request и response
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getUploadedFiles')->willReturn(['file' => $uploadedFile]);

        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse();

        // Вызываем метод контроллера
        $response = $this->videoController->uploadVideo($request, $response, []);

        // Проверяем статус код и сообщение об ошибке
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJson($response->getBody());
        $this->assertStringContainsString('Error processing video', (string)$response->getBody());
    }
}
