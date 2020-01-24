<?php

namespace Chocofamilyme\LaravelPubSub\Amqp;

use Chocofamilyme\LaravelPubSub\Amqp\Message\OutputMessage;
use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Illuminate\Support\Arr;
use PhpAmqpLib\Message\AMQPMessage;
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

    public function __construct(RabbitMQQueue $queue)
    {
        $this->rabbit = $queue;
    }

    /**
     * @param string $routing
     * @param mixed  $body
     * @param array  $properties
     * @param array  $headers
     * @param array  $applicationHeaders
     *
     * @return mixed
     * @throws \Exception
     */
    public function publish(
        $routing,
        $body,
        array $properties = [],
        array $headers = [],
        array $applicationHeaders = []
    ) {
        $correlationId = $headers['correlation_id'] ?? Uuid::uuid4();

        $headers['correlation_id'] = $correlationId;

        /** @var OutputMessage $message */
        $message = $this->createMessage($body, $headers, $applicationHeaders);

        $this->rabbit->getChannel()->basic_publish(
            $message->getMessage(),
            Arr::get($properties, 'exchange', ''),
            $routing,
            Arr::get($properties, 'mandatory', false),
            Arr::get($properties, 'immediate', false),
            Arr::get($properties, 'ticket', null)
        );

        return $correlationId;
    }

    /**
     * @param       $payload
     * @param array $headers
     * @param array $applicationHeaders
     *
     * @return OutputMessage
     * @throws \Exception
     */
    protected function createMessage($payload, array $headers, array $applicationHeaders): OutputMessage
    {
        $headers['application_headers'] = $applicationHeaders;

        return new OutputMessage($payload, $headers);
    }
}
