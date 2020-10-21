<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Amqp\Message;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

class OutputMessage implements MessageInterface
{
    /** @var AMQPMessage */
    private $message;

    /** @var array */
    private $body;

    /** @var array */
    private $headers = [
        'content_type'  => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ];

    /**
     * OutputMessage constructor.
     *
     * @param array $body
     * @param array $headers
     * @param int   $attempts
     *
     * @throws \Exception
     */
    public function __construct(array $body, array $headers = [], int $attempts = 0)
    {
        $this->body = $body;
        $this->initHeaders($body, $headers);

        $this->message = new AMQPMessage(\json_encode($this->getBody()), $this->headers);
        $this->message->set('application_headers', $this->createTable($headers, $attempts));
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeader(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * @param array $body
     * @param array $headers
     *
     * @throws \Exception
     */
    private function initHeaders(array $body, array $headers = [])
    {
        $this->headers['message_id']     = $body['event_id'] ?? Uuid::uuid4()->toString();
        $this->headers['correlation_id'] = $headers['correlation_id'] ?? Uuid::uuid4()->toString();

        $this->headers = array_merge(
            $this->headers,
            $headers
        );
    }

    /**
     * @param array $headers
     * @param int   $attempts
     *
     * @return AMQPTable
     */
    private function createTable(array $headers, int $attempts): AMQPTable
    {
        $table = $headers['application_headers'] ?? [];

        $table['span_id'] = $table['span_id'] ?? 0;
        $table['laravel'] = [
            'attempts' => $attempts,
        ];

        return new AMQPTable($table);
    }

    /**
     * @return AMQPMessage
     */
    public function getMessage(): AMQPMessage
    {
        return $this->message;
    }

    public function getHeaders()
    {
        return $this->headers;
    }
}
