<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Dictionary;
use Chocofamilyme\LaravelPubSub\Exceptions\InvalidEventDeclarationException;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Str;

/**
 * Class PublishEvent
 *
 * Abstract class for publishing events
 */
abstract class PublishEvent implements SendToRabbitMQInterface, ShouldBroadcast
{
    protected const EXCHANGE_TYPE = 'topic';
    protected const EXCHANGE_NAME = null;
    protected const NAME          = null;
    protected const ROUTING_KEY   = null;

    private string $eventId;
    private string $eventCreatedAt;

    public function broadcastOn()
    {
        return [
            new Channel($this->getRoutingKey()),
        ];
    }

    /**
     * Get exchange type for the rabbitmq event
     *
     * override this method in your event if you want non default exchange type
     *
     * @return string
     */
    public function getExchangeType(): string
    {
        return static::EXCHANGE_TYPE;
    }

    /**
     * @return array
     * @throws InvalidEventDeclarationException
     */
    public function broadcastWith(): array
    {
        return [
            'body'       => $this->getBody(),
            'headers'    => $this->getHeaders(),
            'properties' => [
                'exchange' => [
                    'name' => $this->getExchange(),
                    'type' => $this->getExchangeType(),
                ],
                'headers'  => $this->getHeaders(),
            ],
            'model'      => [
                'durable' => $this instanceof DurableEvent,
            ],
        ];
    }

    /**
     * @return array
     * @throws InvalidEventDeclarationException
     */
    private function getBody(): array
    {
        return array_merge(
            $this->toPayload(),
            [
                Dictionary::EVENT_ID_KEY        => $this->getEventId(),
                Dictionary::EVENT_CREATE_AT_KEY => $this->getEventCreatedAt(),
                Dictionary::EVENT_NAME_KEY      => $this->getName(),
            ]
        );
    }

    public function toPayload(): array
    {
        return get_object_vars($this);
    }

    public function getExchange(): string
    {
        if (null === static::EXCHANGE_NAME) {
            throw new InvalidEventDeclarationException(
                "Pubsub events must override constants EXCHANGE_NAME"
            );
        }

        return static::EXCHANGE_NAME;
    }

    public function getRoutingKey(): string
    {
        if (null === static::EXCHANGE_NAME) {
            throw new InvalidEventDeclarationException(
                "Pubsub events must override constants ROUTING_KEY"
            );
        }

        return static::ROUTING_KEY;
    }

    /**
     * Event id
     *
     * @psalm-suppress RedundantPropertyInitializationCheck
     * @return string
     */
    public function getEventId(): string
    {
        return $this->eventId ??= Str::uuid()->toString();
    }

    /**
     * Event name
     */
    public function getName(): string
    {
        return static::NAME ?? static::class;
    }

    public function broadcastAs(): string
    {
        return $this->getName();
    }

    public function getHeaders(): array
    {
        return [
            'message_id' => $this->getEventId(),
        ];
    }

    /**
     * @psalm-suppress RedundantPropertyInitializationCheck
     * @return string
     */
    public function getEventCreatedAt(): string
    {
        return $this->eventCreatedAt ??= CarbonImmutable::now()->toDateTimeString();
    }
}
