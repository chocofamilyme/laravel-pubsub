<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Listeners;

use Chocofamilyme\LaravelPubSub\Broadcasting\Events\BroadcastStarted;
use Chocofamilyme\LaravelPubSub\Events\DurableEvent;
use Chocofamilyme\LaravelPubSub\Events\EventModel;

final class CreateModelListener
{
    public function handle(BroadcastStarted $started): void
    {
        $event = $started->event;
        if (!$event instanceof DurableEvent) {
            return;
        }

        $event->prepare();
        $model = new EventModel(
            [
                'id'          => $event->getEventId(),
                'type'        => EventModel::TYPE_PUB,
                'name'        => $event->getName(),
                'payload'     => $event->getPayload(),
                'exchange'    => $event->getExchange(),
                'routing_key' => $event->getRoutingKey(),
                'created_at'  => $event->getEventCreatedAt(),
            ]
        );

        $model->save();
    }
}
