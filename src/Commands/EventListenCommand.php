<?php

namespace Chocofamilyme\LaravelPubSub\Commands;

use Amqp;
use App\Exceptions\Handler;
use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Illuminate\Console\Command;

class EventListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'event:listen {eventname : Event name, e.g. user.# -> listen to all events starting with user.} {--exchange= : Optional, specifies exchange which should be listened [for default value see app/config/amqp.php]} {--queuename= : Optional, specifies queue name which should be created [default is the same as event name]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to (rabbit) events with this command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exchange = $this->option('exchange') ?? config('amqp.properties.production.exchange');
        $eventName = $this->argument('eventname');
        $queueName = $this->option('queuename') ?? $eventName;

        $this->info("Start listening event $eventName on exchange $exchange, queue name is $queueName");

        Amqp::consume($queueName, function ($message, $resolver) {
            $routingKey = $message->delivery_info['routing_key'];
            $this->info('Received event ' . $routingKey);

            try {
                $eventRouter = new EventRouter();
                $eventRouter->handle($routingKey, $message->body);
            } catch (\Exception $e) {
                $exceptionHandler = new Handler(app());
                $exceptionHandler->report($e);
                echo "Error occured: " . $e->getMessage() . PHP_EOL;
            }

            $resolver->acknowledge($message);
            $this->info('Event processed');
        }, [
            'exchange' => $exchange,
            'routing' => $eventName,
            'persistent' => true // required if you want to listen forever,
        ]);
    }
}
