<?php

/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamily\LaravelPubSub\Tests\TestClasses;

class TestListener
{
    public function handle($event)
    {
        return $event;
    }
}
