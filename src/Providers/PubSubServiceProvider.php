<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Providers;

use Chocofamilyme\LaravelPubSub\Broadcasting\Events\BroadcastEnded;
use Chocofamilyme\LaravelPubSub\Broadcasting\Events\BroadcastStarted;
use Chocofamilyme\LaravelPubSub\Commands\EventListenCommand;
use Chocofamilyme\LaravelPubSub\Listener;
use Chocofamilyme\LaravelPubSub\Listeners\CreateModelListener;
use Chocofamilyme\LaravelPubSub\Listeners\ProccessedModelListener;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

/**
 * Class PubSubServiceProvider
 *
 * @package Chocofamilyme\LaravelPubSub\Providers
 */
class PubSubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
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

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make('events');

        $dispatcher->listen(BroadcastStarted::class, CreateModelListener::class);
        $dispatcher->listen(BroadcastEnded::class, ProccessedModelListener::class);
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
