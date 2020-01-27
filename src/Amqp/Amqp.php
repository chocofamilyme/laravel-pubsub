<?php

namespace Chocofamilyme\LaravelPubSub\Amqp;

use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Illuminate\Queue\QueueManager;
use Ramsey\Uuid\Uuid;

/**
 * Class Amqp
 *
 * @package Chocofamilyme\LaravelPubSub\Amqp
 */
class Amqp
{
    /**
     * @var RabbitMQQueue
     */
    private $rabbit;

    public function __construct(QueueManager $queue, ?string $connection = null)
    {
        $connection   = $connection ?? config('queue.default');
        $this->rabbit = $queue->connection($connection);
    }

    /**
     * @param string $routing
     * @param mixed  $body
     * @param array  $properties
     * @param array  $headers
     *
     * @return mixed
     * @throws \Exception
     */
    public function publish(
        $routing,
        $body,
        array $properties = [],
        array $headers = []
    ) {
        $correlationId = $headers['correlation_id'] ?? Uuid::uuid4();

        $headers['correlation_id'] = $correlationId;

        $correlationId = $this->rabbit->pushRaw($body, $routing, $properties);

        return $correlationId;
    }
}
