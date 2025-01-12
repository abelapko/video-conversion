<?php

namespace App\Tests\Service;

use App\Service\VideoService;
use App\Cloud\YandexCloudStorageService;
use App\Converter\VideoConverter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use org\bovigo\vfs\vfsStream;

class VideoServiceTest extends TestCase
{
    private $parameters;
    private $root;

    protected function setUp(): void
    {
        // Эмулируем файловую систему
        $this->root = vfsStream::setup('root');
        $this->parameters = require getenv('ROOT_PATH') . '/src/Config/parameters.php';

        $uploadPath = vfsStream::newDirectory('uploads')->at($this->root);
        $convertPath = vfsStream::newDirectory('converted')->at($this->root);

        // Мокаем сервисы и конвертер
        $yandexConfig = $this->parameters['yandex'];
        $realYandexService = new YandexCloudStorageService($yandexConfig);

        $mockConverter = $this->createMock(VideoConverter::class);
        $mockConverter->method('convert')->willReturn(true);

        $this->videoService = new VideoService(
            $realYandexService,
            $mockConverter,
            $uploadPath->url(),
            $convertPath->url(),
            $this->createMock(LoggerInterface::class)
        );

        // Создаем тестовый файл
        $this->createTestVideoFile($uploadPath);
    }

    public function testProcessVideo()
    {
        $result = $this->videoService->processVideo($this->getMockUploadedFile());

        $this->assertStringContainsString('https://storage.yandexcloud.net/converted/', $result);
    }

    private function createTestVideoFile($uploadPath)
    {
        file_put_contents(vfsStream::url('root/uploads/test.mp4'), 'dummy video content');
    }

    private function getMockUploadedFile()
    {
        $mock = $this->createMock(\Psr\Http\Message\UploadedFileInterface::class);
        $mock->method('getClientFilename')->willReturn('test.mp4');
        $mock->method('moveTo')->willReturnCallback(function($targetPath) {
            copy(vfsStream::url('root/uploads/test.mp4'), $targetPath);
        });

        return $mock;
    }
}
