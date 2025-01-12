<?php

namespace App\Cloud;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class YandexCloudStorageService
{
    private $s3Client;
    private $bucket;

    public function __construct($config)
    {
        // Создаём экземпляр клиента S3 с параметрами для Яндекс Облака
        $this->s3Client = new S3Client([
            'region' => 'us-east-1',  // Яндекс Облако использует region=us-east-1 (для совместимости с AWS SDK)
            'version' => 'latest',
            'endpoint' => $config['endpoint'],  // Яндекс Object Storage endpoint
            'credentials' => [
                'key'    => $config['access_key'],  // Ваш access key
                'secret' => $config['secret_key'],  // Ваш secret key
            ],
            'signature' => 'v4', // Яндекс поддерживает v4 подпись
            'use_path_style_endpoint' => true, // Для правильной работы с LocalStack
        ]);

        $this->bucket = $config['bucket'];  // Название вашего бакета
    }

    // Метод для загрузки файла в Яндекс Object Storage
    public function uploadFile($filePath, $objectKey): string
    {
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $objectKey,
                'Body'   => fopen($filePath, 'rb'),
                'ACL'    => 'public-read',  // Публичный доступ (если нужно)
            ]);
            echo "File uploaded successfully to Yandex Cloud\n";
            return $this->getFileUrl($objectKey);
        } catch (AwsException $e) {
            echo "Error uploading file: " . $e->getMessage() . "\n";
        }
    }

    // Метод для скачивания файла из Yandex Object Storage
    public function downloadFile($objectKey)
    {
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $objectKey,
            ]);
            return $result['Body'];  // Возвращаем содержимое файла
        } catch (AwsException $e) {
            echo "Error downloading file: " . $e->getMessage() . "\n";
            return null;
        }
    }

    // Метод для получения публичной ссылки на файл из Яндекс Object Storage
    public function getFileUrl($objectKey)
    {
        try {
            $url = $this->s3Client->getObjectUrl($this->bucket, $objectKey);
            return $url;  // Возвращаем ссылку на файл
        } catch (AwsException $e) {
            echo "Error getting file URL: " . $e->getMessage() . "\n";
            return null;  // Возвращаем null в случае ошибки
        }
    }
}
