<?php

declare(strict_types=1);

namespace Chocofamily\LaravelPubSub\Tests\Amqp\Message;

use Chocofamily\LaravelPubSub\Tests\TestCase;
use Chocofamilyme\LaravelPubSub\Message\OutputMessage;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\Uuid;

class OutputMessageTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testGetMessage(): void
    {
        $body = [
            'keyBody' => 'valueBody',
        ];

        $header = [
            'keyCustomHeader' => 'valueCustomHeader',
            'correlation_id'  => Uuid::uuid4()->toString(),
            'message_id'      => Uuid::uuid4()->toString(),
        ];

        $attempts = 1;

        $outputMessage = new OutputMessage($body, $header, $attempts);
        $message       = $outputMessage->getMessage();

        $this->assertEquals(
            $message->getBody(),
            \json_encode($body, JSON_THROW_ON_ERROR),
            'Message body is not correct'
        );

        $this->assertEquals(
            $message->get_properties()['content_type'],
            'application/json',
            'Message content type is not correct'
        );

        $this->assertEquals(
            $message->get_properties()['delivery_mode'],
            AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'Message delivery mode is not correct'
        );

        $this->assertEquals(
            $message->get_properties()['message_id'],
            $header['message_id'],
            'Message id is not correct'
        );

        $this->assertEquals(
            $message->get_properties()['correlation_id'],
            $header['correlation_id'],
            'Message correlation id is not correct'
        );

        $applicationHeaders = $message->get_properties()['application_headers']->getNativeData();

        $this->assertEquals(
            $applicationHeaders['span_id'],
            0,
            'Message span id is not correct'
        );

        $this->assertEquals(
            $applicationHeaders['laravel']['attempts'],
            $attempts,
            'Message laravel attempts is not correct'
        );
    }
}
