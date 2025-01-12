<?php

namespace App\Repository;

use Redis;

class RedisRepository
{
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Записать статус задачи в Redis.
     *
     * @param string $taskId Уникальный идентификатор задачи.
     * @param string $status Статус задачи.
     */
    public function setTaskStatus(string $taskId, string $status)
    {
        $key = 'task_status:' . $taskId;
        // Можно использовать метод SETEX для установки TTL, чтобы через определённое время статус исчезал.
        $this->redis->set($key, $status);
    }

    /**
     * Получить статус задачи из Redis.
     *
     * @param string $taskId Уникальный идентификатор задачи.
     * @return string|null Статус задачи или null, если задача не найдена.
     */
    public function getTaskStatus(string $taskId): ?string
    {
        $key = 'task_status:' . $taskId;
        return $this->redis->get($key);
    }
}
