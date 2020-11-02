<?php

namespace Chocofamilyme\LaravelPubSub\Commands;

use Chocofamilyme\LaravelPubSub\Listener;
use VladimirYuldashev\LaravelQueueRabbitMQ\Console\ConsumeCommand;

class EventListenCommand extends ConsumeCommand
{
    protected $signature = 'event:listen
                            {event? : Event name, e.g. user.# -> listen to all events starting with user.}
                            {connection=rabbitmq : The name of the queue connection to work}
                            {--name=default : The name of the consumer}
                            {--queue= : The names of the queues to work}
                            {--exchange= : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]}
                            {--exchange_type=topic : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]}
                            {--once : Only process the next job on the queue}
                            {--job=laravel : Handler for internal or external message}
                            {--stop-when-empty : Stop when the queue is empty}
                            {--delay=0 : The number of seconds to delay failed jobs}
                            {--backoff=0 : The number of seconds to wait before retrying a job that encountered an uncaught exception}
                            {--max-jobs=0 : The number of jobs to process before stopping}
                            {--max-time=0 : The maximum number of seconds the worker should run}
                            {--force : Force the worker to run even in maintenance mode}
                            {--memory=128 : The memory limit in megabytes}
                            {--sleep=1 : Number of seconds to sleep when no job is available}
                            {--timeout=0 : The number of seconds a child process can run}
                            {--tries=1 : Number of times to attempt a job before logging it failed}
                            {--exclusive=0 : used by only one connection and the queue will be deleted when that connection close}
                            {--consumer_exclusive=0 : request exclusive consumer access, meaning only this consumer can access the queue}
                            {--wait_non_blocking=1 : non-blocking actions}
                            {--exchange_passive=0 : If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not}
                            {--exchange_durable=1 : If set when creating a new exchange, the exchange will be marked as durable}
                            {--exchange_auto_delete=0 : If set, the exchange is deleted when all queues have finished using it}

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
        /** @var string $eventName */
        $eventName = $this->argument('event');
        /** @var string $exchange */
        $exchange  = $this->option('exchange') ?? '';
        /** @var string $queueName */
        $queueName = $this->option('queue') ?? config('queue.connections.rabbitmq.queue');

        $this->info("Start listening event $eventName on exchange $exchange, queue name is $queueName");

        /** @var Listener $listener */
        $listener = $this->worker;
        $listener->setExchange($exchange);

        if ($eventName) {
            $listener->setRoutes(explode(':', $eventName));
        }

        /** @var string $job */
        $job = $this->option('job');
        /** @var string $exchangeType */
        $exchangeType = $this->option('exchange_type');

        $listener->setExchangeType($exchangeType);
        $listener->setExclusive((bool)$this->option('exclusive'));
        $listener->setConsumerExclusive((bool)$this->option('consumer_exclusive'));
        $listener->setJob($job);
        $listener->setMessageTtl(config('queue.connections.rabbitmq.options.message-ttl', 0));
        $listener->setWaitNonBlocking((bool) $this->option('wait_non_blocking'));

        $listener->setExchangePassive((bool) $this->option('exchange_passive'));
        $listener->setExchangeDurable((bool) $this->option('exchange_durable'));
        $listener->setExchangeAutoDelete((bool) $this->option('exchange_auto_delete'));

        parent::handle();
    }
}
