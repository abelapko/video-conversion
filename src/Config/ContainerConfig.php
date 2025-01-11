<?php

namespace App\Config;

use App\Controller\VideoController;
use App\Service\VideoService;
use App\Repository\RabbitMQRepository;
use App\VideoConverter;
use DI\ContainerBuilder;

class ContainerConfig
{
    public static function configure(ContainerBuilder $containerBuilder)
    {
    }
}
