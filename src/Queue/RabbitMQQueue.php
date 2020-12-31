<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue;

use Chocofamilyme\LaravelPubSub\Message\OutputMessage;
use Chocofamilyme\LaravelPubSub\Events\PublishEvent;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQLaravel;
use Exception;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue as Queue;
use Illuminate\Support\Arr;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQQueue extends Queue
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
     *
     * @throws AMQPProtocolChannelException
     */
    public function push($payload, $data = '', $queue = null)
    {
        return $this->pushRaw($payload, $queue, []);
    }

    /**
     * {@inheritdoc}
     *
     * @throws AMQPProtocolChannelException
     */
    public function pushOn($queue, $job, $data = '')
    {
        $job = $job->event ? $job->event : $job;
        return $this->pushRaw(
            $this->createObjectPayload($job, $queue),
            $queue,
            $this->createObjectProperties($job)
        );
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        /** @psalm-suppress InvalidArgument */
        $queue    = $this->getQueue($queue);
        $exchange = Arr::get($options, 'exchange.name', '');

        if (Arr::get($options, 'exchange.declare', false)) {
            $exchange = $exchange ?: $queue;

            $this->declareExchange(
                $exchange,
                Arr::get($options, 'exchange.type', AMQPExchangeType::DIRECT)
            );
        }

        if (Arr::get($options, 'queue.declare', false)) {
            $this->declareQueue($queue);
        }

        if (Arr::get($options, 'queue.bind', false)) {
            $this->bindQueue($queue, $queue, $queue);
        }

        [$message, $correlationId] = $this->getMessage(
            $payload,
            Arr::get($options, 'headers', [])
        );

        $this->channel->basic_publish($message, $exchange, $queue, true, false);

        return $correlationId;
    }

    /**
     * @param array|string $payload
     * @param array $headers
     * @param int $attempts
     *
     * @return array
     * @throws Exception
     */
    protected function getMessage($payload, array $headers = [], int $attempts = 0): array
    {
        if (is_string($payload)) {
            $payload = \json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        }

        $outputMessage = new OutputMessage($payload, $headers, $attempts);

        return [
            $outputMessage->getMessage(),
            $outputMessage->getHeader('correlation_id'),
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

    protected function createObjectPayload($job, $queue)
    {
        if ($job instanceof PublishEvent) {
            return $job->getPayload();
        }

        return parent::createObjectPayload($job, $queue);
    }

    protected function createObjectProperties($job): array
    {
        if ($job instanceof PublishEvent) {
            return [
                'exchange' => [
                    'name' => $job->getExchange(),
                    'type' => $job->getExchangeType(),
                ],
                'headers'  => $job->getHeaders(),
            ];
        }

        return [];
    }
}
