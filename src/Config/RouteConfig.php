<?php

namespace App\Config;

use App\Controller\VideoController;
use Slim\App;

class RouteConfig
{
    public static function configure(App $app)
    {
        // Настройка маршрутов
        $app->post('/upload', [VideoController::class, 'uploadVideo']);

        // Добавляем роут для проверки статуса задачи
        $app->get('/task/{taskId}/status', [VideoController::class, 'checkTaskStatus']);

        // Роут для скачивания сконвертированного видео
        $app->get('/download/{taskId}', [VideoController::class, 'downloadVideo']);
    }
}
