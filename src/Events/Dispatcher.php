<?php

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Closure;
use Exception;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\QueueManager;
use Throwable;

use function json_encode;

class Dispatcher implements DispatcherContract
{
    private DispatcherContract $dispatcher;

    protected Queue $rabbit;

    public function __construct(
        DispatcherContract $eventDispatcher,
        QueueManager $queue,
        Repository $config,
        ?string $connection = null
    ) {
        $this->dispatcher = $eventDispatcher;

        $connection   ??= $config->get('queue.default');
        $this->rabbit = $queue->connection($connection);
    }

    /**
     * Dispatch an event and call the listeners.
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return array|null
     * @throws Exception
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        if ($this->shouldBeSentToRabbitMQ($event)) {
            /** @var PublishEvent $event */
            $this->sendPublishEvent($event);

            return null;
        }

        return $this->dispatcher->dispatch($event, $payload, $halt);
    }


    /**
     * Check wether should be sent to rabbitmq or the normal way
     *
     * @param $event
     *
     * @return bool
     */
    private function shouldBeSentToRabbitMQ($event): bool
    {
        return $event instanceof SendToRabbitMQInterface;
    }

    /**
     * @param SendToRabbitMQInterface $event
     */
    private function sendPublishEvent(SendToRabbitMQInterface $event): void
    {
        $durable = $event instanceof DurableEvent;

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

        if ($durable) {
            $model->save();
        }

        try {
            $payload           = $event->getPayload();
            $payload['_event'] = $event->getName();

            if ($this->isNeedJsonEncode($payload)) {
                $payload = json_encode($payload, JSON_THROW_ON_ERROR);
            }

            /** @psalm-suppress PossiblyInvalidArgument */
            $this->rabbit->pushRaw(
                $payload,
                $event->getRoutingKey(),
                [
                    'exchange' => [
                        'name' => $event->getExchange(),
                        'type' => $event->getExchangeType(),
                    ],
                    'headers'  => $event->getHeaders(),
                ]
            );

            if ($durable) {
                $model->processed_at = CarbonImmutable::now();
                $model->save();
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function isNeedJsonEncode($body): bool
    {
        return !($this->rabbit instanceof RabbitMQQueue || is_string($body));
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param Closure|string|array $events
     * @param Closure|string|null  $listener
     *
     * @return void
     */
    public function listen($events, $listener = null)
    {
        $this->dispatcher->listen($events, $listener);
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     *
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * Register an event subscriber with the dispatcher.
     *
     * @param object|string $subscriber
     *
     * @return void
     */
    public function subscribe($subscriber)
    {
        $this->dispatcher->subscribe($subscriber);
    }

    /**
     * Dispatch an event until the first non-null response is returned.
     *
     * @param string|object $event
     * @param mixed         $payload
     *
     * @return array|null
     */
    public function until($event, $payload = [])
    {
        return $this->dispatcher->until($event, $payload);
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return $this->dispatch($event, $payload, $halt);
    }

    /**
     * Register an event and payload to be fired later.
     *
     * @param string $event
     * @param array  $payload
     *
     * @return void
     */
    public function push($event, $payload = [])
    {
        $this->dispatcher->push($event, $payload);
    }

    /**
     * Flush a set of pushed events.
     *
     * @param string $event
     *
     * @return void
     */
    public function flush($event)
    {
        $this->dispatcher->flush($event);
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param string $event
     *
     * @return void
     */
    public function forget($event)
    {
        $this->dispatcher->forget($event);
    }

    /**
     * Forget all of the queued listeners.
     *
     * @return void
     */
    public function forgetPushed()
    {
        $this->dispatcher->forgetPushed();
    }

    /**
     * Dynamically pass methods to the default dispatcher.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->dispatcher->$method(...$parameters);
    }
}
