<?php
/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamilyme\LaravelPubSub\Amqp\Message;

interface MessageInterface
{
    public function getMessage();

    public function getHeader(string $key, $default = null);

    public function getHeaders();
}
