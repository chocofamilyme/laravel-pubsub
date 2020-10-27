<?php

/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestListener;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\CallQueuedHandler;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQExternal;
use Illuminate\Contracts\Bus\Dispatcher;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class RabbitMQListenerTest extends TestCase
{

    /** @var RabbitMQQueue $rabbitmq */
    private $rabbitmq;

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('pubsub.listen', [
                'test.route' => [
                    TestListener::class,
                ],
            ]);

        $this->app['config']->set('queue', require __DIR__ . '/config/queue.php');

        $this->rabbitmq  = $this->createMock(RabbitMQQueue::class);
        $this->rabbitmq->method('ack');
    }

    public function testItFire()
    {
        $body    = json_encode('test');
        $message = new AMQPMessage($body);

        $message->delivery_info['routing_key'] = 'test.route';

        $rabbitMQListener = new RabbitMQExternal(
            $this->app,
            $this->rabbitmq,
            $message,
            'rabbitmq',
            'test',
            new EventRouter(),
            new CallQueuedHandler(
                $this->app->make(Dispatcher::class),
                $this->app
            )
        );

        $rabbitMQListener->fire();

        $this->assertEquals(CallQueuedHandler::class, get_class($rabbitMQListener->getResolvedJob()));
    }

    public function testGetNameOldFormat()
    {
        $body    = json_encode('test');
        $message = new AMQPMessage($body);

        $message->delivery_info['routing_key'] = 'test.route';

        $rabbitMQListener = new RabbitMQExternal(
            $this->app,
            $this->rabbitmq,
            $message,
            'rabbitmq',
            'test',
            new EventRouter(),
            new CallQueuedHandler(
                $this->app->make(Dispatcher::class),
                $this->app
            )
        );

        $this->assertEquals('test.route', $rabbitMQListener->getName());
    }

    public function testGetName()
    {
        $eventName = 'eventName';
        $body    = json_encode(['name' => 'test', '_event' => $eventName]);
        $message = new AMQPMessage($body);

        $message->delivery_info['routing_key'] = 'test.route';

        $rabbitMQListener = new RabbitMQExternal(
            $this->app,
            $this->rabbitmq,
            $message,
            'rabbitmq',
            'test',
            new EventRouter(),
            new CallQueuedHandler(
                $this->app->make(Dispatcher::class),
                $this->app
            )
        );

        $this->assertEquals($eventName, $rabbitMQListener->getName());
    }
}
