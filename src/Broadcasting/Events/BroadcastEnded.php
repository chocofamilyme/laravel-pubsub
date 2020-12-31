<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Broadcasting\Events;

final class BroadcastEnded
{
    public $event;

    public function __construct($event)
    {
        $this->event = $event;
    }
}
