<?php

namespace Chocofamilyme\LaravelPubSub\Events;

use Ramsey\Uuid\Uuid;

/**
 * Extend your event of this class if your message should be sent to RabbitMQ
 *
 * Class SendToRabbitMQAbstract
 * @package Chocofamilyme\LaravelPubSub\Events
 */
abstract class SendToRabbitMQAbstract implements SendToRabbitMQInterface
{
    /**
     * Get exchange type for the rabbitmq event
     *
     * override this method in your event if you want non default exchange type
     *
     * @return string
     */
    public function getExchangeType(): string
    {
        return 'topic';
    }

    /**
     * Get headers which should be sent in the message
     *
     * override this method in your event if you want non default headers in your rabbitmq message
     *
     * @return array
     * @throws \Exception
     */
    public function getHeaders(): array
    {
        return [
            'message_id' => Uuid::uuid4()->toString()
        ];
    }

    /**
     * Get all public class properties
     *
     * @return array
     */
    public function getPublicProperties(): array
    {
        return get_object_vars($this);
    }
}