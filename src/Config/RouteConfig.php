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
        // Добавьте другие маршруты здесь
    }
}
