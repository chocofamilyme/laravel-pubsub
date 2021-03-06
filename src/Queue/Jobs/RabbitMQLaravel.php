<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue\Jobs;

use JsonException;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

use function json_decode;

class RabbitMQLaravel extends RabbitMQJob
{
    /**
     * {@inheritdoc}
     */
    public function release($delay = 0): void
    {
        if ($delay > 0) {
            sleep($delay);
        }

        $this->released = true;
        $this->rabbitmq->reject($this, !$this->hasFailed());
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function payload()
    {
        if (is_null($this->decoded)) {
            $this->decoded = json_decode($this->getRawBody(), true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->decoded;
    }
}
