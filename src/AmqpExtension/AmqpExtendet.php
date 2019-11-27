<?php

namespace Chocofamilyme\LaravelPubSub\AmqpExtension;

use Bschmitt\Amqp\Publisher;
use Bschmitt\Amqp\Request;
use Bschmitt\Amqp\Message;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpExtendet
{
    /**
     * @param string $routing
     * @param mixed $message
     * @param array $properties
     * @param array $headers
     * @param array $applicationHeaders
     * @throws \Bschmitt\Amqp\Exception\Configuration
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function publish($routing, $message, array $properties = [], array $headers = [], array $applicationHeaders = [])
    {
        $properties['routing'] = $routing;

        /* @var Publisher $publisher */
        $publisher = app()->make('Bschmitt\Amqp\Publisher');
        $publisher
            ->mergeProperties($properties)
            ->setup();

        if (is_string($message)) {
            $defaultHeaders = ['content_type' => 'text/plain', 'delivery_mode' => 2];

            $applicationHeadersTable = new AMQPTable($applicationHeaders);
            $message = new Message($message, array_merge($defaultHeaders, $headers));
            $message->set('application_headers', $applicationHeadersTable);
        }

        $publisher->publish($routing, $message);
        Request::shutdown($publisher->getChannel(), $publisher->getConnection());
    }
}