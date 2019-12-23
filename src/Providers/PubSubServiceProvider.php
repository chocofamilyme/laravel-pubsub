<?php

namespace Chocofamilyme\LaravelPubSub\Providers;

use Chocofamilyme\LaravelPubSub\Commands\EventListenCommand;
use Chocofamilyme\LaravelPubSub\Listener;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class PubSubServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge our config with application config
        $this->mergeConfigFrom(
            __DIR__.'/../config/queue.php', 'queue'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../config/pubsub.php', 'pubsub'
        );

        if ($this->app->runningInConsole()) {
            $this->app->singleton('rabbitmq.listener', function () {
                $isDownForMaintenance = function () {
                    return $this->app->isDownForMaintenance();
                };

                return new Listener(
                    $this->app['queue'],
                    $this->app['events'],
                    $this->app[ExceptionHandler::class],
                    $isDownForMaintenance
                );
            });

            $this->app->singleton(EventListenCommand::class, static function ($app) {
                return new EventListenCommand(
                    $app['rabbitmq.listener'],
                    $app['cache.store']
                );
            });

            // Register artisan commands
            $this->commands([
                EventListenCommand::class,
            ]);
        }
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Config
        $this->publishes([
            __DIR__.'/../config/pubsub.php' => config_path('pubsub.php'),
        ]);

        // Add class and it's facade
        $this->app->bind('AmqpExtendet', 'Chocofamilyme\LaravelPubSub\AmqpExtension\AmqpExtendet');
        if (!class_exists('AmqpExtendet')) {
            class_alias('Chocofamilyme\LaravelPubSub\AmqpExtension\AmqpExtendetFacade', 'AmqpExtendet');
        }
    }

    /**
     *
     */
    public function provides()
    {
        return ['AmqpExtendet'];
    }
}
