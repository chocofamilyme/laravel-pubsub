<?php

declare(strict_types=1);

namespace Application\Console\Commands;

use Carbon\CarbonImmutable;
use Chocofamilyme\LaravelPubSub\Events\EventModel;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

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

    private Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }

    public function handle(): int
    {
        $events = EventModel::whereNull('processed_at')->where('type', 'pub')->orderBy('created_at');

        $events
            ->cursor()
            ->chunk(self::CHUNK_SIZE)
            ->each(
                function (EventModel $eventModel) {
                    try {
                        $this->app->make('queue')->connection(null)->pushRaw(
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
