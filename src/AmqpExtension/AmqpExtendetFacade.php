<?php

namespace Chocofamilyme\LaravelPubSub\AmqpExtension;

use Illuminate\Support\Facades\Facade;

class AmqpExtendetFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'AmqpExtendet';
    }
}
