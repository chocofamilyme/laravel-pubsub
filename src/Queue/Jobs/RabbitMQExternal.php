<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue\Jobs;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Events\EventModel;
use Chocofamilyme\LaravelPubSub\Exceptions\NotFoundListenerException;
use Chocofamilyme\LaravelPubSub\Queue\CallQueuedHandler;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * Class ListenerMQJob
 *
 * Запускает обработку сообщения опредеденным классом Laravel Listeners
 *
 * @package Chocofamilyme\LaravelPubSub\Queue\Jobs
 */
class RabbitMQExternal extends RabbitMQLaravel
{
    /**
     * @var EventRouter
     */
    protected $eventRouter;

    /**
     * ListenerMQJob constructor.
     *
     * @param Container         $container
     * @param RabbitMQQueue     $rabbitmq
     * @param AMQPMessage       $message
     * @param string            $connectionName
     * @param string            $queue
     * @param EventRouter       $eventRouter
     * @param CallQueuedHandler $queueHandler
     */
    public function __construct(
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue,
        EventRouter $eventRouter,
        CallQueuedHandler $queueHandler
    ) {
        parent::__construct(
            $container,
            $rabbitmq,
            $message,
            $connectionName,
            $queue
        );

        $this->eventRouter = $eventRouter;
        $this->instance    = $queueHandler;
    }

    /**
     * @throws NotFoundListenerException
     */
    public function fire()
    {
        $payload   = $this->payload();
        $listeners = $this->eventRouter->getListeners($this->getName());

        $model = new EventModel([
            'id'            => $this->getEventId(),
            'type'          => EventModel::TYPE_SUB,
            'name'          => $this->getName(),
            'payload'       => $payload,
            'exchange'      => $this->message->getExchange(),
            'routing_key'   => $this->message->getRoutingKey(),
            'created_at'    => CarbonImmutable::now()->toDateTimeString()
        ]);

        $model->save();

        foreach ($listeners as $listener) {
            $this->instance->call($this, $listener, $payload);
        }
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string
    {
        /** @psalm-suppress InternalProperty */
        return $this->payload()['_event'] ?? Arr::get($this->message->delivery_info, 'routing_key');
    }

    public function getEventId(): string
    {
        return $this->payload()['id'] ?? Str::uuid()->toString();
    }

    public function failed($e)
    {
        EventModel::where('id', $this->getEventId())->update([
            'exception' => (string) $e,
            'failed_at' => CarbonImmutable::now()->toDateTimeString()
        ]);
    }
}
