<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Listeners;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Broadcasting\Events\BroadcastEnded;
use Chocofamilyme\LaravelPubSub\Events\DurableEvent;
use Chocofamilyme\LaravelPubSub\Events\EventModel;

final class ProcessedModelListener
{
    public function handle(BroadcastEnded $ended): void
    {
        $event = $ended->event;
        if (!$event instanceof DurableEvent) {
            return;
        }

        $model = EventModel::find($event->getEventId());

        $model->processed_at = CarbonImmutable::now();
        $model->update();
    }
}
