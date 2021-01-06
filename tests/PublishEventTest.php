<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestInvalidEvent;
use Chocofamily\LaravelPubSub\Tests\TestClasses\TestValidEvent;
use Chocofamilyme\LaravelPubSub\Dictionary;
use Chocofamilyme\LaravelPubSub\Events\DurableEvent;
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

    public function testBroadcastWith(): void
    {
        $event = new TestValidEvent();

        $this->assertEquals(
            [
                'body'       => [
                    Dictionary::EVENT_ID_KEY        => $event->getEventId(),
                    Dictionary::EVENT_CREATE_AT_KEY => $event->getEventCreatedAt(),
                    Dictionary::EVENT_NAME_KEY      => $event->getName(),
                    'data'                          => 'data',
                ],
                'headers'    => [
                    'message_id' => $event->getEventId(),
                ],
                'properties' => [
                    'exchange' => [
                        'name' => 'valid_exchange',
                        'type' => 'topic',
                    ],
                    'headers'  => [
                        'message_id' => $event->getEventId(),
                    ],
                ],
                'model'      => [
                    'durable' => false,
                ],
            ],
            $event->broadcastWith()
        );
    }

    public function testItFire(): void
    {
        $event  = new TestValidEvent();
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
