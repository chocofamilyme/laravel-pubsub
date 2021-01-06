<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue\Factory;

use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\CallQueuedHandler;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQExternal;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQLaravel;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\Job as JobContract;
use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class RabbitMQFactory
{
    /**
     * @param string        $jobType
     * @param Container     $container
     * @param RabbitMQQueue $rabbitmq
     * @param AMQPMessage   $message
     * @param string        $connectionName
     * @param string        $queue
     *
     * @return JobContract
     * @throws BindingResolutionException
     */
    public static function make(
        string $jobType,
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ): JobContract {
        if ($jobType === 'external') {
            return new RabbitMQExternal(
                $container,
                $rabbitmq,
                $message,
                $connectionName,
                $queue,
                new EventRouter(),
                new CallQueuedHandler(
                    $container->make(Dispatcher::class),
                    $container
                )
            );
        }

        if ($jobType === 'laravel') {
            return new RabbitMQLaravel(
                $container,
                $rabbitmq,
                $message,
                $connectionName,
                $queue
            );
        }

        throw new InvalidArgumentException('Handler not found', 404);
    }
}
