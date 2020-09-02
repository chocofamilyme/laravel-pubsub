<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests\TestClasses;

use Chocofamilyme\LaravelPubSub\Events\PublishEvent;

class TestInvalidEvent extends PublishEvent
{

    public function toPayload(): array
    {
        return [
            'data' => 'data'
        ];
    }
}