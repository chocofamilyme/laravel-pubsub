<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamilyme\LaravelPubSub\Providers\PubSubServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(PubSubServiceProvider::class);
    }
}
