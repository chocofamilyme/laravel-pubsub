<?php

/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestInvalidEvent;
use Chocofamily\LaravelPubSub\Tests\TestClasses\TestValidEvent;
use Chocofamilyme\LaravelPubSub\Events\Dispatcher;
use Chocofamilyme\LaravelPubSub\Exceptions\InvalidEventDeclarationException;

class PublishEventTest extends TestCase
{
    private Dispatcher $dispatcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = app()->make(Dispatcher::class);
        $this->loadMigrationsFrom(__DIR__ . '/tests/database');
    }

    public function testItFire()
    {
        $event = new TestValidEvent();
        $result = $this->dispatcher->dispatch($event);

        $this->assertEmpty($result);
    }

    public function testItFails()
    {
        $this->expectException(InvalidEventDeclarationException::class);

        $event = new TestInvalidEvent();
        $this->dispatcher->dispatch($event);
    }
}
