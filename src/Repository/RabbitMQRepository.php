<?php

namespace App\Repository;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQRepository
{
    private $connection;
    private $channel;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD')
        );
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare('video_conversion_queue', false, true, false, false);
    }

    public function sendToQueue(array $messageBody)
    {
        $msg = new AMQPMessage(
            json_encode($messageBody),
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $this->channel->basic_publish($msg, '', 'video_conversion_queue');
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
