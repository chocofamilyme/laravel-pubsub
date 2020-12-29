<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Amqp;

use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueManager;

class Amqp
{
    protected Queue $rabbit;

    public function __construct(QueueManager $queue, Repository $config, ?string $connection = null)
    {
        $connection   ??= $config->get('queue.default');
        $this->rabbit = $queue->connection($connection);
    }

    /**
     * @param string $routing
     * @param        $body
     * @param array  $properties
     * @param array  $headers
     *
     * @return mixed
     * @throws Exception
     */
    public function publish(
        string $routing,
        $body,
        array $properties = [],
        array $headers = []
    ) {
        $properties['headers'] = $headers;

        if ($this->isNeedJsonEncode($body)) {
            $body = \json_encode($body, JSON_THROW_ON_ERROR);
        }

        return $this->rabbit->pushRaw($body, $routing, $properties);
    }

    protected function isNeedJsonEncode($body): bool
    {
        return !($this->rabbit instanceof RabbitMQQueue) && !is_string($body);
    }
}
