<?php

namespace Chocofamilyme\LaravelPubSub\Events;

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

            /** @var SendToRabbitMQAbstract $event */
            // Append _event to payload, it's the name of the event class
            $eventPublicProperties = $event->getPublicProperties();
            $eventPublicProperties['_event'] = $event->getEventName();

            $this->container->get('Amqp')->publish($event->getRoutingKey(), json_encode($eventPublicProperties), [
                    'exchange' => [
                        'name' => $event->getExchange(),
                        'type' => $event->getExchangeType(),
                    ],
                    'headers' => $event->getHeaders()
                ]
            );
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
}
