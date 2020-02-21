<?php

namespace Chocofamilyme\LaravelPubSub\Events;

interface SendToRabbitMQInterface
{
    /**
     * Get exchange where to publish the message
     *
     *
     * @return string|null
     */
    public function getExchange(): ?string;

    /**
     * Get routing key, where the message will be routed
     *
     * @return string
     */
    public function getRoutingKey(): string;


}