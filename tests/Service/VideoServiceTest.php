<?php

namespace App\Tests\Service;

use App\Service\VideoService;
use App\Cloud\YandexCloudStorageService;
use App\Converter\VideoConverter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

class VideoServiceTest extends TestCase
{
    private $cloudStorageService;
    private $videoConverter;
    private $videoService;
    private $uploadPath;
    private $convertPath;

    protected function setUp(): void
    {
        // Мокируем зависимости
        $this->cloudStorageService = $this->createMock(YandexCloudStorageService::class);
        $this->videoConverter = $this->createMock(VideoConverter::class);

        // Пути для загрузки и конвертации
        $this->uploadPath = '/path/to/upload';
        $this->convertPath = '/path/to/convert';

        // Инициализируем VideoService с моками
        $this->videoService = new VideoService(
            $this->cloudStorageService,
            $this->videoConverter,
            $this->uploadPath,
            $this->convertPath
        );
    }

    public function testProcessVideoSuccessfully()
    {
        // Мокируем файл
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('test_video.mp4');
        $uploadedFile->method('moveTo')->will($this->returnCallback(function ($path) {
            file_put_contents($path, 'test'); // Эмулируем перемещение файла
        }));

        // Мокируем поведение облачного хранилища и конвертера
        $this->cloudStorageService
            ->expects($this->exactly(2))  // Проверяем два вызова
            ->method('uploadFile')
            ->willReturnOnConsecutiveCalls(
                true, // Первый вызов
                true  // Второй вызов
            );

        $this->videoConverter
            ->expects($this->once())  // Конвертация
            ->method('convert')
            ->with($this->uploadPath . '/test_video.mp4', $this->convertPath . '/test_video.avi')
            ->willReturn(true);

        // Выполняем процесс видео
        $result = $this->videoService->processVideo($uploadedFile);

        // Проверяем результат
        $expectedUrl = 'https://storage.yandexcloud.net/converted/test_video.avi';
        $this->assertEquals($expectedUrl, $result);
    }

    public function testProcessVideoThrowsExceptionOnError()
    {
        // Мокируем файл
        $uploadedFile = $this->createMock(UploadedFileInterface::class);
        $uploadedFile->method('getClientFilename')->willReturn('test_video.mp4');
        $uploadedFile->method('moveTo')->will($this->returnCallback(function ($path) {
            file_put_contents($path, 'test');
        }));

        // Мокируем поведение облачного хранилища с ошибкой
        $this->cloudStorageService
            ->expects($this->exactly(1))  // Ожидаем один вызов
            ->method('uploadFile')
            ->willThrowException(new \RuntimeException('Upload failed'));

        // Проверяем, что метод выбрасывает исключение при ошибке
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error processing video: Upload failed');

        // Выполняем процесс видео, ожидая исключение
        $this->videoService->processVideo($uploadedFile);
    }
}
