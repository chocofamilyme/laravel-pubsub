<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Events\EventModel;
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
        if ($this->isQueueDeclared($name)) {
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
