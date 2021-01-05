<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * @property string $id
 * @property string $name
 * @property array $payload
 * @property array $headers
 * @property string $exchange
 * @property string $exchange_type
 * @property string $routing_key
 * @property Carbon $created_at
 * @property Carbon|CarbonImmutable|string|null $processed_at
 */
class EventModel extends Model
{
    public const TYPE_PUB = 'pub';
    public const TYPE_SUB = 'sub';

    public $incrementing = false;
    public const UPDATED_AT = null;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $dates = [
        'created_at',
        'processed_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
    ];

    protected ?PublishEvent $originalEvent = null;

    public function getTable()
    {
        return config('pubsub.tables.events', parent::getTable());
    }

    public function setOriginalEvent(PublishEvent $event): self
    {
        $this->originalEvent = $event;

        return $this;
    }

    public function isDurable(): bool
    {
        if (null === $this->originalEvent) {
            return false;
        }

        return $this->originalEvent instanceof DurableEvent;
    }

    public function amqpProperties(): array
    {
        return [
            'exchange' => [
                'name' => $this->exchange,
                'type' => $this->exchange_type,
            ],
            'headers'  => $this->headers,
        ];
    }
}
