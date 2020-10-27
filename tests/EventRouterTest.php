<?php

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestListener;
use Chocofamilyme\LaravelPubSub\Exceptions\NotFoundListenerException;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;

/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */
class EventRouterTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('pubsub.listen', [
                'test.route' => [
                    TestListener::class,
                ],
            ]);
    }

    public function testItGetListeners()
    {
        $eventRoute = new EventRouter();
        $listeners  = $eventRoute->getListeners('test.route');

        $this->assertEquals($listeners[0], TestListener::class);
    }

    public function testItNotFoundListener()
    {
        $eventRoute = new EventRouter();

        $this->expectException(NotFoundListenerException::class);
        $eventRoute->getListeners('notfound.route');
    }
}
