<?php

namespace Chocofamilyme\LaravelPubSub\Queue\Connectors;

use Exception;
use Illuminate\Support\Arr;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Connectors\RabbitMQConnector as BaseRabbitMQConnector;

/**
 * Class RabbitMQConnector
 *
 * @package Chocofamilyme\LaravelPubSub\Queue\Connectors
 */
class RabbitMQConnector extends BaseRabbitMQConnector
{
    /**
     * @param array $config
     *
     * @return AbstractConnection
     * @throws Exception
     */
    protected function createConnection(array $config): AbstractConnection
    {
        /** @var AbstractConnection $connection */
        $connection = Arr::get($config, 'connection', AMQPLazyConnection::class);
        $hosts      = Arr::shuffle(Arr::get($config, 'hosts', []));

        return $connection::create_connection(
            $hosts,
            $this->filter(Arr::get($config, 'options', []))
        );
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function filter(array $array): array
    {
        foreach ($array as $index => &$value) {
            if (is_array($value)) {
                $value = $this->filter($value);
                continue;
            }

            // If the value is null then remove it.
            if ($value === null) {
                unset($array[$index]);
                continue;
            }
        }

        return $array;
    }
}
