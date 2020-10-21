<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests\TestClasses;

use Chocofamilyme\LaravelPubSub\Events\PublishEvent;

class TestValidEvent extends PublishEvent
{
    protected const EXCHANGE_NAME = 'valid_exchange';
    protected const NAME = 'valid_name';
    protected const ROUTING_KEY = 'valid_routing_key';

    public function toPayload(): array
    {
        return [
            'data' => 'data'
        ];
    }
}
