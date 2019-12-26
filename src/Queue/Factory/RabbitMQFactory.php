<?php

namespace Chocofamilyme\LaravelPubSub\Queue\Factory;

use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\Listeners\RabbitMQListener;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */
class RabbitMQFactory
{
    /**
     * @param string        $handler
     * @param Container     $container
     * @param RabbitMQQueue $rabbitmq
     * @param AMQPMessage   $message
     * @param string        $connectionName
     * @param string        $queue
     *
     * @return JobContract
     */
    public static function make(
        string $handler,
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ): JobContract {
        if ($handler == 'RabbitMQListener') {
            return new RabbitMQListener(
                $container,
                $rabbitmq,
                $message,
                $connectionName,
                $queue,
                new EventRouter()
            );
        } elseif ($handler == 'RabbitMQJob') {
            return new RabbitMQJob(
                $container,
                $rabbitmq,
                $message,
                $connectionName,
                $queue,
            );
        }

        throw new \InvalidArgumentException('Handler not found', 404);
    }
}
