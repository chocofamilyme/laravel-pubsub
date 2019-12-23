<?php

namespace Chocofamilyme\LaravelPubSub\Commands;

use Chocofamilyme\LaravelPubSub\Listener;
use VladimirYuldashev\LaravelQueueRabbitMQ\Console\ConsumeCommand;

class EventListenCommand extends ConsumeCommand
{
    protected $signature = 'event:listen
                            {connection : The name of the queue connection to work}
                            {--event= : Event name, e.g. user.# -> listen to all events starting with user.} 
                            {--queue= : The names of the queues to work}
                            {--exchange= : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]} 
                            {--exchange_type=topic : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]} 
                            {--once : Only process the next job on the queue}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                           
                            {--consumer-tag}
                            {--prefetch-size=0}
                            {--prefetch-count=0}
                           ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to (rabbit) events with this command';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $exchange  = $this->option('exchange') ?? '';
        $eventName = $this->option('event');
        $queueName = $this->option('queue') ?? $eventName;

        $this->info("Start listening event $eventName on exchange $exchange, queue name is $queueName");

        /** @var Listener $listener */
        $listener = $this->worker;
        $listener->setExchange($exchange);
        $listener->setRoutes(explode(':', $eventName));
        $listener->setExchangeType($this->option('exchange_type'));

        parent::handle();
    }
}
