<?php

namespace Chocofamilyme\LaravelPubSub\Listeners;

use App\Exceptions\Handler;
use Chocofamilyme\LaravelPubSub\Exceptions\NoListenerException;
use Throwable;

/**
 * This class is beeing used from EventListenCommand as Router for events
 *
 * Class EventRouter
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
     * This method routes the events to their listeners based on event name
     *
     * @param string $eventName
     * @param string $payload
     * @throws NoListenerException
     */
    public function handle(string $eventName, string $payload): void
    {
        if (!$this->checkIfEventHasListeners($eventName)) {
            throw new NoListenerException($eventName . ' has no listeners. Please check App\Listeners\EventRouter $listen property');
        }

        $payload = $this->convertPayloadToArrayIfPossible($payload);

        foreach ($this->listen[$eventName] as $listener) {
            try {
                (new $listener)->handle($payload);
            } catch (\Exception $e) {
                $this->logException($e);
            }
        }
    }

    /**
     * Logs exception
     *
     * @param $exception
     * @throws \Exception
     */
    private function logException(Throwable $exception)
    {
        $exceptionHandler = new Handler(app());
        $exceptionHandler->report($exception);
        echo "Error occured: " . $exception->getMessage() . PHP_EOL;
    }

    /**
     * Converts payload to array if it is possible, otherwise let it like it was
     *
     * @param $payload
     * @return mixed
     */
    private function convertPayloadToArrayIfPossible($payload)
    {
        $array = json_decode($payload, true);
        if (is_null($array)) {
            return $payload;
        }

        return $array;
    }

    /**
     * Checks if event has listeners
     *
     * @param string $eventName
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
