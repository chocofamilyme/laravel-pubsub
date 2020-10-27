<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Exceptions\InvalidEventDeclarationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Class PublishEvent
 *
 * Abstract class for publishing events
 */
abstract class PublishEvent implements SendToRabbitMQInterface
{
    protected const EXCHANGE_NAME = null;
    protected const NAME = null;
    protected const ROUTING_KEY = null;

    private string $eventId;
    private string $eventCreatedAt;

    public function prepare(): void
    {
        if (
            empty(static::EXCHANGE_NAME) ||
            empty(static::ROUTING_KEY)
        ) {
            throw new InvalidEventDeclarationException("Pubsub events must override constants EXCHANGE_NAME, ROUTING_KEY");
        }

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
            '_eventId' => $this->getEventId(),
            '_eventCreatedAt' => $this->getEventCreatedAt(),
        ]);
    }

    public function toPayload(): array
    {
        return get_object_vars($this);
    }

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
    public function getRoutingKey(): string
    {
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
    public function getName(): string
    {
        return static::NAME ?? last(explode('\\', static::class));
        ;
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
}
