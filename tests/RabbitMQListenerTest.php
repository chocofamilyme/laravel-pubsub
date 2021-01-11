<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests;

use Chocofamily\LaravelPubSub\Tests\TestClasses\TestListener;
use Chocofamilyme\LaravelPubSub\Dictionary;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\CallQueuedHandler;
use Chocofamilyme\LaravelPubSub\Queue\Jobs\RabbitMQExternal;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Str;
use PhpAmqpLib\Message\AMQPMessage;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class RabbitMQListenerTest extends TestCase
{
    /** @var RabbitMQQueue $rabbitmq */
    private $rabbitmq;

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set(
            'pubsub.listen',
            [
                'test.route' => [
                    'durable' => false,
                    'listeners' => [
                        TestListener::class,
                    ]
                ],
            ]
        );

        $this->app['config']->set('queue', require __DIR__ . '/config/queue.php');

        $this->rabbitmq = $this->createMock(RabbitMQQueue::class);
        $this->rabbitmq->method('ack');
    }

    public function testItFire(): void
    {
        $body = \json_encode(
            [
                'body' => 'value',
            ],
            JSON_THROW_ON_ERROR
        );

        $message = new AMQPMessage($body);
        $message->setDeliveryInfo('', '', '', 'test.route');

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

    public function testGetNameOldFormat(): void
    {
        $body    = json_encode('test', JSON_THROW_ON_ERROR);
        $message = new AMQPMessage($body);

        $message->setDeliveryInfo('', '', '', 'test.route');

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

    public function testGetNameAndId(): void
    {
        $eventName = 'eventName';
        $id        = Str::uuid()->toString();
        $body      = json_encode(
            [
                'name'                     => 'test',
                Dictionary::EVENT_NAME_KEY => $eventName,
                Dictionary::EVENT_ID_KEY   => $id,
            ],
            JSON_THROW_ON_ERROR
        );
        $message   = new AMQPMessage($body);

        $message->setDeliveryInfo('', '', '', 'test.route');

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
        $this->assertEquals($id, $rabbitMQListener->getJobId());
    }
}
