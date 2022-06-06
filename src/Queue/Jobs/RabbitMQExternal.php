<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue\Jobs;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Dictionary;
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
     * @throws \JsonException
     */
    public function fire()
    {
        $payload = $this->payload();

        $jobId = $this->getJobId();

        if ($this->eventRouter->isEventDurable($this->getName())) {
            EventModel::firstOrCreate(
                [
                    'id' => $jobId
                ],
                [
                    'type'        => EventModel::TYPE_SUB,
                    'name'        => $this->getName(),
                    'payload'     => $payload,
                    'headers'     => $this->getRabbitMQMessageHeaders(),
                    'exchange'    => $this->message->getExchange(),
                    'routing_key' => $this->message->getRoutingKey(),
                    'created_at'  => CarbonImmutable::now()->toDateTimeString(),
                ]
            );
        }

        $payload['application_headers'] = $this->getRabbitMQMessageHeaders();

        $listeners = $this->eventRouter->getListeners($this->getName());
        foreach ($listeners as $listener) {
            $this->instance->call($this, $listener, $payload);
        }
    }

    /**
     * @return string
     */
    public function getJobId()
    {
        return $this->decoded[Dictionary::EVENT_ID_KEY] ?? Str::uuid()->toString();
    }

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string
    {
        $name = $this->payload()[Dictionary::EVENT_NAME_KEY] ?? $this->message->getRoutingKey();

        if (null === $name) {
            throw new \RuntimeException("The name is not defined");
        }

        return $name;
    }

    public function failed($e)
    {
        if ($this->eventRouter->isEventDurable($this->getName())) {
            EventModel::where('id', $this->getJobId())->update(
                [
                    'exception' => (string)$e,
                    'failed_at' => CarbonImmutable::now()->toDateTimeString(),
                ]
            );
        }
    }
}
