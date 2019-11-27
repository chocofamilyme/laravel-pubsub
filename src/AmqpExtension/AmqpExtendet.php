<?php

namespace Chocofamilyme\LaravelPubSub\AmqpExtension;

use Bschmitt\Amqp\Publisher;
use Bschmitt\Amqp\Request;
use Bschmitt\Amqp\Message;

class AmqpExtendet
{
    /**
     * @param string $routing
     * @param mixed $message
     * @param array $properties
     * @param array $headers
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Bschmitt\Amqp\Exception\Configuration
     */
    public function publish($routing, $message, array $properties = [], array $headers = [])
    {
        $properties['routing'] = $routing;

        /* @var Publisher $publisher */
        $publisher = app()->make('Bschmitt\Amqp\Publisher');
        $publisher
            ->mergeProperties($properties)
            ->setup();

        if (is_string($message)) {
            $defaultHeaders = ['content_type' => 'text/plain', 'delivery_mode' => 2];

            $message = new Message($message, array_merge($defaultHeaders, $headers));
        }

        $publisher->publish($routing, $message);
        Request::shutdown($publisher->getChannel(), $publisher->getConnection());
    }
}