<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Message;

use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Ramsey\Uuid\Uuid;

use function json_encode;

class OutputMessage implements MessageInterface
{
    private AMQPMessage $message;
    private array $body;
    private array $headers = [
        'content_type'  => 'application/json',
        'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
    ];

    /**
     * @param array $body
     * @param array $headers
     * @param int   $attempts
     *
     * @throws JsonException
     */
    public function __construct(array $body, array $headers = [], int $attempts = 0)
    {
        $this->body = $body;
        $this->initHeaders($headers);

        $this->message = new AMQPMessage(\json_encode($this->getBody(), JSON_THROW_ON_ERROR), $this->headers);
        $this->message->set('application_headers', $this->createTable($headers, $attempts));
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getHeader(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    private function initHeaders(array $headers = []): void
    {
        $this->headers = array_merge(
            $this->headers,
            $headers
        );

        $this->headers['message_id']     ??= Uuid::uuid4()->toString();
        $this->headers['correlation_id'] ??= Uuid::uuid4()->toString();
    }

    private function createTable(array $headers, int $attempts): AMQPTable
    {
        $table = $headers['application_headers'] ?? [];

        $table['span_id'] = $table['span_id'] ?? 0;
        $table['laravel'] = [
            'attempts' => $attempts,
        ];

        return new AMQPTable($table);
    }

    public function getMessage(): AMQPMessage
    {
        return $this->message;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
