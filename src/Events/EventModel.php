<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Class DurableEvent
 * @package Chocofamilyme\LaravelPubSub\Events
 *
 * @property string $id
 * @property string $name
 * @property array $payload
 * @property string $exchange
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
        'payload' => 'array'
    ];

    public function getTable()
    {
        return config('pubsub.tables.events', parent::getTable());
    }
}