<?php
/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamilyme\LaravelPubSub\Queue\Listeners;

use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Illuminate\Container\Container;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * Class ListenerMQJob
 *
 * Запускает обработку сообщения опредеденным классом Laravel Listeners
 *
 * @package Chocofamilyme\LaravelPubSub\Queue\Jobs
 */
class RabbitMQListener extends RabbitMQJob
{
    /**
     * @var EventRouter
     */
    protected $eventRouter;

    /**
     * ListenerMQJob constructor.
     *
     * @param Container     $container
     * @param RabbitMQQueue $rabbitmq
     * @param AMQPMessage   $message
     * @param string        $connectionName
     * @param string        $queue
     * @param EventRouter   $eventRouter
     */
    public function __construct(
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue,
        EventRouter $eventRouter
    ) {
        parent::__construct(
            $container,
            $rabbitmq,
            $message,
            $connectionName,
            $queue
        );

        $this->eventRouter = $eventRouter;
    }

    /**
     * @throws \Chocofamilyme\LaravelPubSub\Exceptions\NotFoundListenerException
     */
    public function fire()
    {
        $payload = $this->payload();

        $listeners = $this->eventRouter->getListeners($this->message->delivery_info['routing_key']);

        foreach ($listeners as $listener) {
            [$class, $method] = Str::parseCallback($listener, 'handle');
            ($this->instance = $this->resolve($class))->{$method}($payload);
        }
    }
}
