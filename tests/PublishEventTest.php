<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestInvalidEvent;
use Chocofamily\LaravelPubSub\Tests\TestClasses\TestValidEvent;
use Chocofamilyme\LaravelPubSub\Exceptions\InvalidEventDeclarationException;
use Illuminate\Contracts\Events\Dispatcher;

class PublishEventTest extends TestCase
{
    private Dispatcher $dispatcher;

    public function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = $this->app->make(Dispatcher::class);
    }

    public function testItFire(): void
    {
        $event = new TestValidEvent();
        $result = $this->dispatcher->dispatch($event);

        $this->assertEmpty($result);
    }

    public function testItFails(): void
    {
        $this->expectException(InvalidEventDeclarationException::class);

        $event = new TestInvalidEvent();
        $this->dispatcher->dispatch($event);
    }
}
