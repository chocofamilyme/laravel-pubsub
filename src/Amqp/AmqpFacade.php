<?php

namespace Chocofamilyme\LaravelPubSub\Amqp;

use Illuminate\Support\Facades\Facade;

class AmqpFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Amqp';
    }
}
