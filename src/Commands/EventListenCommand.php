<?php

namespace Chocofamilyme\LaravelPubSub\Commands;

use Chocofamilyme\LaravelPubSub\Listener;
use VladimirYuldashev\LaravelQueueRabbitMQ\Console\ConsumeCommand;

class EventListenCommand extends ConsumeCommand
{
    protected $signature = 'event:listen
                            {event? : Event name, e.g. user.# -> listen to all events starting with user.}
                            {connection=rabbitmq : The name of the queue connection to work}
                            {--queue= : The names of the queues to work}
                            {--exchange= : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]}
                            {--exchange_type=topic : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]}
                            {--once : Only process the next job on the queue}
                            {--job=laravel : Handler for internal or external message}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=3 : Number of seconds to sleep when no job is available}
                            {--timeout=60 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--exclusive=0 : used by only one connection and the queue will be deleted when that connection close}
                            {--consumer_exclusive=0 : request exclusive consumer access, meaning only this consumer can access the queue}

                            {--consumer-tag}
                            {--prefetch-size=0}
                            {--prefetch-count=1}
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
        $eventName = $this->argument('event');
        $exchange  = $this->option('exchange') ?? '';
        $queueName = $this->option('queue') ?? config('queue.connections.rabbitmq.queue');

        $this->info("Start listening event $eventName on exchange $exchange, queue name is $queueName");

        /** @var Listener $listener */
        $listener = $this->worker;
        $listener->setExchange($exchange);

        if($eventName) {
            $listener->setRoutes(explode(':', $eventName));
        }

        $listener->setExchangeType($this->option('exchange_type'));
        $listener->setExclusive($this->option('exclusive'));
        $listener->setConsumerExclusive($this->option('consumer_exclusive'));
        $listener->setJob($this->option('job'));
        $listener->setMessageTtl(config('queue.connections.rabbitmq.options.message-ttl', 0));

        parent::handle();
    }
}
