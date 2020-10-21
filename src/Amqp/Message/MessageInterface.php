<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Amqp\Message;

interface MessageInterface
{
    public function getMessage();

    public function getHeader(string $key, $default = null);

    public function getHeaders();
}
