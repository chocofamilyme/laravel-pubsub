<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

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
 * @property Carbon|CarbonImmutable|string|null $failed_at
 * @property string|null $exception
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

    protected array $originalEvent = [];

    public function getTable()
    {
        return config('pubsub.tables.events', parent::getTable());
    }

    public function setOriginalEvent(array $event): self
    {
        $this->originalEvent = $event;

        return $this;
    }

    public function isDurable(): bool
    {
        if (empty($this->originalEvent)) {
            return false;
        }

        return $this->originalEvent['model']['durable'] ?? false;
    }

    public function amqpProperties(): array
    {
        return [
            'exchange' => [
                'name' => $this->exchange,
                'type' => $this->exchange_type,
            ],
            'headers'  => $this->headers ?? [],
        ];
    }
}
