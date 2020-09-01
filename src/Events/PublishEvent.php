<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class PublishEvent
 *
 * Abstract class for publishing events
 */
abstract class PublishEvent implements SendToRabbitMQInterface
{
    protected const EXCHANGE_NAME   = 'exchange';
    protected const NAME            = 'name';
    protected const ROUTING_KEY     = 'routing.key';

    private string $eventId;
    private string $eventCreatedAt;

    public function prepare(): void
    {
        $this->eventId = Str::uuid()->toString();
        $this->eventCreatedAt = CarbonImmutable::now()->toDateTimeString();
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
        return 'topic';
    }

    /**
     * Message payload
     *
     * @return array
     */
    public function getPayload(): array
    {
        return array_merge($this->toPayload(), [
            '_eventId'        => $this->getEventId(),
            '_eventCreatedAt' => $this->getEventCreatedAt(),
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
    public function getEventId(): string
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
            'message_id' => $this->getEventId(),
        ];
    }

    /**
     * Creation date (UTC)
     *
     * @return string
     */
    public function getEventCreatedAt(): string
    {
        return $this->eventCreatedAt;
    }

    public function getPublicProperties(): array
    {
        return $this->getPayload();
    }
}
