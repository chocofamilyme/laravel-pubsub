<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

/**
 * Class PublishEvent
 *
 * Abstract class for publishing events
 */
abstract class PublishEvent extends SendToRabbitMQAbstract
{
    protected const EXCHANGE_NAME   = 'exchange';
    protected const NAME            = 'name';
    protected const ROUTING_KEY     = 'routing.key';

    private string $eventId;

    /**
     * Message payload
     *
     * @return array
     */
    public function getPayload(): array
    {
        return array_merge($this->toPayload(), [
            'id'        => $this->getId(),
            'createdAt' => $this->getCreatedAt(),
        ]);
    }

    abstract public function toPayload(): array;

    /**
     * Exchange in queue borker
     *
     * @return string
     */
    public function getExchange(): string
    {
        return static::EXCHANGE_NAME;
    }

    /**
     * Event route
     *
     * @return string
     */
    public function getRoutingKey(): string {
        return static::ROUTING_KEY;
    }

    /**
     * Event id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->eventId;
    }

    /**
     * Event name
     */
    public function getName(): string {
        return static::NAME;
    }

    public function getHeaders(): array
    {
        return [
            'message_id' => $this->getId(),
        ];
    }

    /**
     * Creation date (UTC)
     *
     * @return string
     */
    abstract public function getCreatedAt(): string;

    public function getPublicProperties(): array
    {
        return $this->getPayload();
    }
}
