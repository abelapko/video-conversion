<?php

namespace App\Repository;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQRepository
{
    private $connection;
    private $channel;

    public function getChannel()
    {
        return $this->channel;
    }

    // Прокидываем зависимость AMQPStreamConnection через конструктор
    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
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

    // Метод для получения сообщения из очереди
    public function receiveFromQueue(callable $callback)
    {
        $this->channel->basic_consume(
            'video_conversion_queue', // Название очереди
            '', // Логин/идентификатор потребителя
            false, // Нет ожидания ответа
            true, // Автоматическое подтверждение
            false, // Нет эксклюзивности
            false, // Нет ожидания при перезапуске
            $callback // Функция обратного вызова для обработки сообщений
        );

        // Ожидание сообщений
        while($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
