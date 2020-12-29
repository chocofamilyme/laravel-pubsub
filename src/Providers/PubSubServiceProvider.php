<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Providers;

use Chocofamilyme\LaravelPubSub\Amqp\Amqp;
use Chocofamilyme\LaravelPubSub\Amqp\AmqpFacade;
use Chocofamilyme\LaravelPubSub\Commands\EventListenCommand;
use Chocofamilyme\LaravelPubSub\Events\Dispatcher;
use Chocofamilyme\LaravelPubSub\Listener;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Collection;
use VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Connectors\RabbitMQConnector;

/**
 * Class PubSubServiceProvider
 *
 * @package Chocofamilyme\LaravelPubSub\Providers
 */
class PubSubServiceProvider extends LaravelQueueRabbitMQServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();

        // Merge our config with application config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/queue.php',
            'queue'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pubsub.php',
            'pubsub'
        );

        if ($this->app->runningInConsole()) {
            $this->app->singleton(
                'rabbitmq.listener',
                function () {
                    $isDownForMaintenance = function (): bool {
                        return $this->app->isDownForMaintenance();
                    };

                    /** @psalm-suppress UndefinedInterfaceMethod */
                    return new Listener(
                        $this->app['queue'],
                        $this->app['events'],
                        $this->app[ExceptionHandler::class],
                        $isDownForMaintenance
                    );
                }
            );

            $this->app->singleton(
                EventListenCommand::class,
                static function ($app) {
                    return new EventListenCommand(
                        $app['rabbitmq.listener'],
                        $app['cache.store']
                    );
                }
            );

            // Register artisan commands
            $this->commands(
                [
                    EventListenCommand::class,
                ]
            );
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Config
        $this->publishes(
            [
                __DIR__ . '/../config/pubsub.php' => config_path('pubsub.php'),
            ]
        );
        $this->publishes(
            [
                __DIR__
                . '/../../database/migrations/create_pubsub_events_table.php.stub' => $this->getMigrationFileName(),
            ],
            'migrations'
        );

        /**
         * @var QueueManager $queue
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $queue = $this->app['queue'];

        $queue->addConnector(
            'rabbitmq',
            function (): ConnectorInterface {
                return new RabbitMQConnector($this->app->make('events'));
            }
        );

        $this->app->bind(
            'Amqp',
            function ($app) {
                return new Amqp($app['queue'], $app['config']);
            }
        );

        if (!class_exists('Amqp')) {
            class_alias(AmqpFacade::class, 'Amqp');
        }

        $this->app->extend(
            'events',
            function (DispatcherContract $baseDispatcher) {
                return new Dispatcher($baseDispatcher, $this->app->make('Amqp'));
            }
        );
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['Amqp'];
    }

    /**
     * Returns existing migration file if found, else uses the current timestamp.
     *
     * @return string
     */
    protected function getMigrationFileName(): string
    {
        $timestamp = date('Y_m_d_His');

        $filenameSuffix = '_create_pubsub_events_table.php';

        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(
                function ($path) use ($filenameSuffix) {
                    return glob($path . '*' . $filenameSuffix);
                }
            )->push($this->app->databasePath() . "/migrations/{$timestamp}{$filenameSuffix}")
            ->first();
    }
}
