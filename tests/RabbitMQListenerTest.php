<?php
/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestListener;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\Listeners\RabbitMQListener;
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

        $rabbitMQListener = new RabbitMQListener(
            $this->app,
            $rabbitmq,
            $message,
            'rabbitmq',
            'test',
            new EventRouter()
        );

        $rabbitMQListener->fire();

        $this->assertEquals(TestListener::class, get_class($rabbitMQListener->getResolvedJob()));
    }
}
