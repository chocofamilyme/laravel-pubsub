<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Commands;

use Chocofamilyme\LaravelPubSub\Events\EventModel;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\QueueManager;

/**
 * Class EventRePublish
 *
 * Задача отправляет в Message Broker не опубликованных события
 */
final class EventRePublish extends Command
{
    private const CHUNK_SIZE = 1000;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:republish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Таск для отправки в Message Broker не опубликованных события';

    private QueueManager $queue;

    public function __construct(QueueManager $queue)
    {
        $this->queue = $queue;

        parent::__construct();
    }

    /**
     * @psalm-suppress InvalidArgument
     */
    public function handle(): int
    {
        $events = EventModel::whereNull('processed_at')->where('type', 'pub')->orderBy('created_at');

        $events
            ->cursor()
            ->chunk(self::CHUNK_SIZE)
            ->each(
                function (EventModel $eventModel) {
                    try {
                        $this->queue->connection()->pushRaw(
                            $eventModel->payload,
                            $eventModel->routing_key,
                            $eventModel->amqpProperties(),
                        );
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            );

        return 0;
    }
}
