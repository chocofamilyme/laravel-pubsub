<?php

namespace Chocofamilyme\LaravelPubSub\Queue\Factory;

use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQCommon;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQLaravel;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */
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
     */
    public static function make(
        string $jobType,
        Container $container,
        RabbitMQQueue $rabbitmq,
        AMQPMessage $message,
        string $connectionName,
        string $queue
    ): JobContract {
        if ($jobType == 'common') {
            return new RabbitMQCommon(
                $container,
                $rabbitmq,
                $message,
                $connectionName,
                $queue,
                new EventRouter()
            );
        } elseif ($jobType == 'laravel') {
            return new RabbitMQLaravel(
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
