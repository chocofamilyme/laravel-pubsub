<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Message;

use PhpAmqpLib\Message\AMQPMessage;

interface MessageInterface
{
    public function getMessage(): AMQPMessage;

    public function getHeader(string $key, $default = null);

    public function getHeaders(): array;
}
