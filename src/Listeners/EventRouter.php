<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Listeners;

use Chocofamilyme\LaravelPubSub\Exceptions\NotFoundListenerException;

/**
 * This class is beeing used from EventListenCommand as Router for events
 *
 * Class EventRouter
 *
 * @package App\Listeners
 */
class EventRouter
{
    protected $listen;

    /**
     * EventRouter constructor.
     */
    public function __construct()
    {
        $this->listen = config('pubsub.listen');
    }

    /**
     * Возвращает слушателя и его метод
     * Отвечающие за обработку ссобщения
     *
     * @param string $eventName
     *
     * @return array
     * @throws NotFoundListenerException
     */
    public function getListeners(string $eventName): array
    {
        $this->checkIfEventHasListeners($eventName);

        return $this->listen[$eventName]['listeners'];
    }

    /**
     * @param string $eventName
     * @return bool
     * @throws NotFoundListenerException
     */
    public function isEventDurable(string $eventName): bool
    {
        $this->checkIfEventHasListeners($eventName);

        return $this->listen[$eventName]['durable'] ?? false;
    }

    /**
     * Checks if event has listeners
     *
     * @param string $eventName
     *
     * @return void
     * @throws NotFoundListenerException
     */
    private function checkIfEventHasListeners(string $eventName): void
    {
        if (!array_key_exists($eventName, $this->listen)) {
            throw new NotFoundListenerException(
                "$eventName has no listeners. Please check App\Listeners\EventRouter \$listen property"
            );
        }

        if (!array_key_exists('listeners', $this->listen[$eventName])) {
            throw new NotFoundListenerException(
                "$eventName has no listeners. Please check App\Listeners\EventRouter \$listen property"
            );
        }
    }
}
