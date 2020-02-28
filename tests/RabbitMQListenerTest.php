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
use ReflectionClass;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class RabbitMQListenerTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('pubsub.listen', [
                'test.route' => [
                    TestListener::class,
                ],
            ]
        );

        $this->app['config']->set('queue', require __DIR__.'/config/queue.php');
    }

    public function testItFire()
    {
        $body    = json_encode('test');
        $message = new AMQPMessage($body);

        $message->delivery_info['routing_key'] = 'test.route';

        $rabbitmq = (new ReflectionClass(RabbitMQQueue::class))->newInstanceWithoutConstructor();

        $rabbitMQListener = new RabbitMQExternal(
            $this->app,
            $rabbitmq,
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

        $this->assertEquals(TestListener::class, get_class($rabbitMQListener->getResolvedJob()));
    }

    public function testGetNameOldFormat()
    {
        $body    = json_encode('test');
        $message = new AMQPMessage($body);

        $message->delivery_info['routing_key'] = 'test.route';

        $rabbitmq = (new ReflectionClass(RabbitMQQueue::class))->newInstanceWithoutConstructor();

        $rabbitMQListener = new RabbitMQExternal(
            $this->app,
            $rabbitmq,
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

        $rabbitmq = (new ReflectionClass(RabbitMQQueue::class))->newInstanceWithoutConstructor();

        $rabbitMQListener = new RabbitMQExternal(
            $this->app,
            $rabbitmq,
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
