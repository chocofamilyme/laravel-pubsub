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
        if (!$this->checkIfEventHasListeners($eventName)) {
            throw new NotFoundListenerException(
                "$eventName has no listeners. Please check App\Listeners\EventRouter \$listen property"
            );
        }

        return $this->listen[$eventName];
    }

    /**
     * Checks if event has listeners
     *
     * @param string $eventName
     *
     * @return bool
     */
    private function checkIfEventHasListeners(string $eventName): bool
    {
        if (!array_key_exists($eventName, $this->listen)) {
            return false;
        }

        return true;
    }
}
