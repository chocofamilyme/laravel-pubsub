<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue\Jobs;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Events\EventModel;
use Chocofamilyme\LaravelPubSub\Exceptions\NotFoundListenerException;
use Chocofamilyme\LaravelPubSub\Queue\CallQueuedHandler;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Illuminate\Container\Container;
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
    protected EventRouter $eventRouter;

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

        if ($this->isSubscribeRecordEnabled()) {
            $model = new EventModel(
                [
                    'id'          => $this->getEventId(),
                    'type'        => EventModel::TYPE_SUB,
                    'name'        => $this->getName(),
                    'payload'     => $payload,
                    'exchange'    => $this->message->getExchange(),
                    'routing_key' => $this->message->getRoutingKey(),
                    'created_at'  => CarbonImmutable::now()->toDateTimeString(),
                ]
            );
            $model->save();
        }

        foreach ($listeners as $listener) {
            $this->instance->call($this, $listener, $payload);
        }
    }

    public function isSubscribeRecordEnabled(): bool
    {
        return config('pubsub.record_sub_events', false);
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string
    {
        $name = $this->payload()['_event'] ?? $this->message->getRoutingKey();

        if (null === $name) {
            throw new \RuntimeException("The name is not defined");
        }

        return $name;
    }

    public function getEventId(): string
    {
        return $this->payload()['id'] ?? Str::uuid()->toString();
    }

    public function failed($e)
    {
        if ($this->isSubscribeRecordEnabled()) {
            EventModel::where('id', $this->getEventId())->update(
                [
                    'exception' => (string)$e,
                    'failed_at' => CarbonImmutable::now()->toDateTimeString(),
                ]
            );
        }
    }
}
