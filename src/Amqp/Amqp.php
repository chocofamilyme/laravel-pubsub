<?php

namespace Chocofamilyme\LaravelPubSub\Amqp;

use Chocofamilyme\LaravelPubSub\Amqp\Message\OutputMessage;
use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Arr;
use PhpAmqpLib\Exchange\AMQPExchangeType;
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

        $exchange = Arr::get($properties, 'exchange', '');
        if ($exchange) {
            $this->rabbit->declareExchange(
                $exchange,
                Arr::get($properties, 'exchange_type', AMQPExchangeType::TOPIC)
            );
        }

        /** @var OutputMessage $message */
        $message = new OutputMessage($body, $headers);

        $this->rabbit->getChannel()->basic_publish(
            $message->getMessage(),
            $exchange,
            $routing,
            Arr::get($properties, 'mandatory', false),
            Arr::get($properties, 'immediate', false),
            Arr::get($properties, 'ticket', null)
        );

        return $correlationId;
    }
}
