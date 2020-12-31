<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Broadcasting\Events;

final class BroadcastStarted
{
    public $event;

    public function __construct($event)
    {
        $this->event = $event;
    }
}
