<?php

namespace Chocofamilyme\LaravelPubSub\Events;

use Carbon\CarbonImmutable;
use Illuminate\Events\Dispatcher as BaseDispatcher;

/**
 * Class WalletDispatcher
 *
 * @package App\Events
 */
class Dispatcher extends BaseDispatcher
{
    /**
     * Dispatch an event and call the listeners.
     *
     * @param string|object $event
     * @param mixed $payload
     * @param bool $halt
     * @return array|null
     * @throws \Exception
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        if ($this->shouldBeSentToRabbitMQ($event)) {
            /** @var PublishEvent $event */
            $this->sendPublishEvent($event);

            return null;
        }

        return parent::dispatch($event, $payload, $halt);
    }


    /**
     * Check wether should be sent to rabbitmq or the normal way
     *
     * @param $event
     * @return bool
     */
    private function shouldBeSentToRabbitMQ($event): bool
    {
        return $event instanceof SendToRabbitMQInterface;
    }

    private function sendPublishEvent(SendToRabbitMQInterface $event): void
    {
        $durable = $event instanceof DurableEvent;

        $event->prepare();

        $model = new EventModel([
            'id'          => $event->getEventId(),
            'type'        => EventModel::TYPE_PUB,
            'name'        => $event->getName(),
            'payload'     => $event->getPayload(),
            'exchange'    => $event->getExchange(),
            'routing_key' => $event->getRoutingKey(),
            'created_at'  => $event->getEventCreatedAt(),
        ]);

        if ($durable) {
            $model->save();
        }

        try {
            $eventPayload = $event->getPayload();
            $eventPayload['_event'] = $event->getName();

            $this->container->get('Amqp')->publish($event->getRoutingKey(), json_encode($eventPayload), [
                    'exchange' => [
                        'name' => $event->getExchange(),
                        'type' => $event->getExchangeType(),
                    ],
                    'headers' => $event->getHeaders()
                ]
            );

            if ($durable) {
                $model->processed_at = CarbonImmutable::now();
                $model->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
