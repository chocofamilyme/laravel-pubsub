<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Exceptions\InvalidEventDeclarationException;
use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Carbon;
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

    public $afterCommit = true;

    public function broadcastOn()
    {
        $durable = $this instanceof DurableEvent;

        $this->prepare();
//
//        $model = new EventModel(
//            [
//                'id'          => $this->getEventId(),
//                'type'        => EventModel::TYPE_PUB,
//                'name'        => $this->getName(),
//                'payload'     => $this->getPayload(),
//                'exchange'    => $this->getExchange(),
//                'routing_key' => $this->getRoutingKey(),
//                'created_at'  => $this->getEventCreatedAt(),
//            ]
//        );
//
//        if ($durable) {
//            $model->save();
//        }
//
//        try {
//            $payload           = $this->getPayload();
//            $payload['_event'] = $this->getName();
//
//            if ($this->isNeedJsonEncode($payload)) {
//                $payload = json_encode($payload, JSON_THROW_ON_ERROR);
//            }
//
//            /** @psalm-suppress PossiblyInvalidArgument */
//            $this->rabbit->pushRaw(
//                $payload,
//                $this->getRoutingKey(),
//                [
//                    'exchange' => [
//                        'name' => $this->getExchange(),
//                        'type' => $this->getExchangeType(),
//                    ],
//                    'headers'  => $this->getHeaders(),
//                ]
//            );
//
//            if ($durable) {
//                $model->processed_at = CarbonImmutable::now();
//                $model->save();
//            }
//        } catch (Throwable $e) {
//            report($e);
//        }
    }

    //protected function isNeedJsonEncode($body): bool
    //{
    //    return !($this->rabbit instanceof RabbitMQQueue || is_string($body));
    //}

    protected function prepare(): void
    {
        if (
            empty(static::EXCHANGE_NAME) ||
            empty(static::ROUTING_KEY)
        ) {
            throw new InvalidEventDeclarationException(
                "Pubsub events must override constants EXCHANGE_NAME, ROUTING_KEY"
            );
        }

        $this->eventId        ??= Str::uuid()->toString();
        $this->eventCreatedAt ??= CarbonImmutable::now()->toDateTimeString();
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

    public function broadcastWith(): array
    {
        $this->getPayload();
    }

    /**
     * Message payload
     *
     * @return array
     */
    public function getPayload(): array
    {
        $this->prepare();
        return array_merge(
            $this->toPayload(),
            [
                '_eventId'        => $this->getEventId(),
                '_eventCreatedAt' => $this->getEventCreatedAt(),
                '_event'          => $this->getName(),
            ]
        );
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

    public function broadcastQueue(): string
    {
        return $this->getRoutingKey();
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
     * Creation date (UTC)
     *
     * @return string
     */
    public function getEventCreatedAt(): string
    {
        return $this->eventCreatedAt;
    }
}
