<?php

namespace Chocofamilyme\LaravelPubSub\AmqpExtension;

use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Chocofamilyme\LaravelPubSub\Exceptions\InvalidArgumentException;
use Illuminate\Support\Arr;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class AmqpExtendet
 *
 * @package Chocofamilyme\LaravelPubSub\AmqpExtension
 */
class AmqpExtendet
{
    /**
     * @param string $routing
     * @param mixed  $message
     * @param array  $properties
     * @param array  $headers
     * @param array  $applicationHeaders
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function publish(
        $routing,
        $message,
        array $properties = [],
        array $headers = [],
        array $applicationHeaders = []
    ) {
        /** @var RabbitMQQueue $queue */
        $queue = app()->get('queue');

        if (!$queue instanceof RabbitMQQueue) {
            throw new InvalidArgumentException('Queue worker should be instance RabbitMQQueue');
        }

        /** @var AMQPMessage $message */
        [$message, $correlationId] = $queue->createMessage($message);

        foreach ($headers as $name => $value) {
            $message->set($name, $value);
        }

        if ($applicationHeaders) {
            $message->set('application_headers', $applicationHeaders);
        }

        $queue->getChannel()->basic_publish(
            $message,
            Arr::get($properties, 'exchange', ''),
            $routing,
            Arr::get($properties, 'mandatory', false),
            Arr::get($properties, 'immediate', false),
            Arr::get($properties, 'ticket', null)
        );

        return $correlationId;
    }
}
