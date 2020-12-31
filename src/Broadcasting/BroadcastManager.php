<?php

namespace Chocofamilyme\LaravelPubSub\Broadcasting;

use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcherContract;
use Illuminate\Contracts\Events\Dispatcher;
use Chocofamilyme\LaravelPubSub\Broadcasting\Events\BroadcastStarted;
use Chocofamilyme\LaravelPubSub\Broadcasting\Events\BroadcastEnded;
use Illuminate\Broadcasting\BroadcastManager as BaseBroadcastManager;

/**
 * @mixin \Illuminate\Contracts\Broadcasting\Broadcaster
 */
class BroadcastManager extends BaseBroadcastManager
{
    /**
     * Queue the given event for broadcast.
     *
     * @param mixed $event
     * @return void
     */
    public function queue($event)
    {
        if ($event instanceof ShouldBroadcastNow) {
            return $this->app->make(BusDispatcherContract::class)->dispatchNow(new BroadcastEvent(clone $event));
        }

        $queue = null;

        if (method_exists($event, 'broadcastQueue')) {
            $queue = $event->broadcastQueue();
        } elseif (isset($event->broadcastQueue)) {
            $queue = $event->broadcastQueue;
        } elseif (isset($event->queue)) {
            $queue = $event->queue;
        }

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make('events');
        $dispatcher->dispatch(new BroadcastStarted($event));

        $this->app->make('queue')->connection($event->connection ?? null)->pushOn(
            $queue,
            new BroadcastEvent(clone $event)
        );

        $dispatcher->dispatch(new BroadcastEnded($event));
    }
}
