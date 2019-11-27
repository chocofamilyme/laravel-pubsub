<?php

namespace Chocofamilyme\LaravelPubSub\Providers;

use Chocofamilyme\LaravelPubSub\Commands\EventListenCommand;
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
            __DIR__ . '/../config/amqp.php', 'amqp'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pubsub.php', 'pubsub'
        );

        // Register artisan commands
        $this->commands([
            EventListenCommand::class,
        ]);
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
            __DIR__ . '/../config/amqp.php' => config_path('amqp.php'),
            __DIR__ . '/../config/pubsub.php' => config_path('pubsub.php'),
        ]);

        // Add class and it's facade
        $this->app->bind('AmqpExtendet', 'Chocofamilyme\LaravelPubSub\AmqpExtension\AmqpExtendet');
        class_alias('Chocofamilyme\LaravelPubSub\AmqpExtension\AmqpExtendetFacade', 'AmqpExtendet');
    }
}
