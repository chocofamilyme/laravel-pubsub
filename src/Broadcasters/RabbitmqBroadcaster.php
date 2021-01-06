<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Broadcasters;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Dictionary;
use Chocofamilyme\LaravelPubSub\Events\EventModel;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Contracts\Queue\Factory;

class RabbitmqBroadcaster extends Broadcaster
{
    private const DRIVER = 'rabbitmq';

    private Factory $manager;

    public function __construct(Factory $manager)
    {
        $this->manager = $manager;
    }

    /** @psalm-suppress InvalidArgument */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $queue      = $this->manager->connection(self::DRIVER);
        $routingKey = $this->getRoutingKey($channels);
        $eventModel = $this->persist($payload, $routingKey);

        $queue->pushRaw(
            $eventModel->payload,
            $routingKey,
            $eventModel->amqpProperties()
        );

        if ($eventModel->isDurable()) {
            $eventModel->processed_at = CarbonImmutable::now();
            $eventModel->update();
        }
    }

    protected function persist(array $event, string $routingKey): EventModel
    {
        if ($model = EventModel::find($event['body'][Dictionary::EVENT_ID_KEY])) {
            return $model;
        }

        $model = new EventModel();
        $model->setOriginalEvent($event);
        $model->setRawAttributes(
            [
                'id'            => $event['body'][Dictionary::EVENT_ID_KEY],
                'type'          => EventModel::TYPE_PUB,
                'name'          => $event['body'][Dictionary::EVENT_NAME_KEY],
                'payload'       => \json_encode($event['body'], JSON_THROW_ON_ERROR),
                'headers'       => \json_encode($event['headers'], JSON_THROW_ON_ERROR),
                'exchange'      => $event['properties']['exchange']['name'],
                'exchange_type' => $event['properties']['exchange']['type'],
                'routing_key'   => $routingKey,
                'created_at'    => $event['body'][Dictionary::EVENT_CREATE_AT_KEY],
            ]
        );

        if ($model->isDurable()) {
            $model->saveOrFail();
        }

        return $model;
    }

    /**
     * @param array $channels
     *
     * @return string
     */
    protected function getRoutingKey(array $channels): string
    {
        if (count($channels) === 0) {
            throw new \RuntimeException('Channels is empty');
        }

        return (string)array_shift($channels);
    }

    /** @psalm-suppress InvalidReturnType */
    public function auth($request)
    {
        //
    }

    /** @psalm-suppress InvalidReturnType */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }
}
