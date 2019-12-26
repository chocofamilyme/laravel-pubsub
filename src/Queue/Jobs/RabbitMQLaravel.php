<?php

namespace Chocofamilyme\LaravelPubSub\Queue\Jobs;

use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

class RabbitMQLaravel extends RabbitMQJob
{
    /**
     * {@inheritdoc}
     */
    public function release($delay = 0): void
    {
        $this->released = true;
        $this->rabbitmq->reject($this, !$this->hasFailed());
    }
}
