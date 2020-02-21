<?php

namespace Chocofamilyme\LaravelPubSub\Queue\Jobs;

use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;

class RabbitMQLaravel extends RabbitMQJob
{
    /** @var array */
    protected $payload;

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
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload()
    {
        if (is_null($this->payload)) {
            $this->payload = json_decode($this->getRawBody(), true);
        }

        return $this->payload;
    }
}
