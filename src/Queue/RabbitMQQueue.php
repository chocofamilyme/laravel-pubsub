<?php

/** @noinspection PhpRedundantCatchClauseInspection */

namespace Chocofamilyme\LaravelPubSub\Queue;

use Chocofamilyme\LaravelPubSub\Amqp\Message\OutputMessage;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQLaravel;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue as Queue;
use Illuminate\Support\Arr;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQQueue extends Queue implements QueueContract
{
    /**
     * The RabbitMQ connection instance.
     *
     * @var AbstractConnection
     */
    protected $connection;

    /**
     * The RabbitMQ channel instance.
     *
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * List of already declared exchanges.
     *
     * @var array
     */
    protected $exchanges = [];

    /**
     * List of already declared queues.
     *
     * @var array
     */
    protected $queues = [];

    /**
     * List of already bound queues to exchanges.
     *
     * @var array
     */
    protected $boundQueues = [];

    /**
     * Current job being processed.
     *
     * @var RabbitMQLaravel
     */
    protected $currentJob;

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $queue    = $this->getQueue($queue);
        $exchange = '';

        if (Arr::get($options, 'publisher.exchange.declare', false)) {
            $this->declareExchange($queue);
            $exchange = $queue;
        }

        if (Arr::get($options, 'publisher.queue.declare', false)) {
            $this->declareQueue($queue);
        }

        if (Arr::get($options, 'publisher.queue.bind', false)) {
            $this->bindQueue($queue, $queue, $queue);
        }

        [$message, $correlationId] = $this->createMessage($payload);

        $this->channel->basic_publish($message, $exchange, $queue, true, false);

        return $correlationId;
    }

    /**
     * @param     $payload
     * @param int $attempts
     *
     * @return array
     * @throws \Exception
     */
    protected function createMessage($payload, int $attempts = 0): array
    {
        $outputMessage = new OutputMessage($payload, [], $attempts);

        return [
            $outputMessage->getMessage(),
            $outputMessage->getMessage()->get_properties()['correlation_id'],
        ];
    }

    public function declareQueue(
        string $name,
        bool $durable = true,
        bool $autoDelete = false,
        array $arguments = []
    ): void {
        if (in_array($name, $this->queues, true)) {
            return;
        }

        $this->channel->queue_declare(
            $name,
            false,
            $durable,
            Arr::pull($arguments, 'exclusive', false),
            $autoDelete,
            false,
            new AMQPTable($arguments)
        );
    }
}
