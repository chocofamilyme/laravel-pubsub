# Upgrade v6 -> v7
- change format of `config/pubsub.php` `listen` field. Add `durable` option, for recording incoming events in database (by default `false`).

Before:
```
listen => [
         'UserNotified' => [
             NotifyAboutDeviceChangeListener::class,
         ]
     ],
```
Now: 
```
listen => [
        'UserNotified' => [
                     'durable' => true,
                     'listeners' => [
                         NotifyAboutDeviceChangeListener::class,
                     ],
                 ]
    ],
```

    

- deprecated `config/pubsub.php` `record_sub_events` field, instead use `durable` option in `listen` field for corresponding event. (see above)

# Upgrade v5 -> v6
- Update the event table to see the migration create_pubsub_events_table.php.stub. Added column exchange_type
- Set environment BROADCAST_DRIVER = rabbitmq
- Add to config/broadcasting.php rabbitmq driver
```php
...
'connections' => [
        'rabbitmq' => [
            'driver' => 'rabbitmq',
        ],
...
]
```

# Upgrade v3 -> v4

* Publish migration and config files
```bash  
php artisan vendor:publish --provider="Chocofamilyme\LaravelPubSub\Providers\PubSubServiceProvider"
```

* Check `config/pubsub.php` file. Add next lines if they are not in file
```php
    'tables' => [
        'events' => 'pubsub_events'
    ],
```

* Default migration file creates `pubsub_events` table. Table name stored as `tables.events` value in `config/pubsub.php`.

* Replace all extends `Chocofamilyme\LaravelPubSub\Events\SendToRabbitMQAbstract` to `Chocofamilyme\LaravelPubSub\Events\PublishEvent`.
* If `getPublicProperties()` was overridden change it to `toPayload()` method. 
* If `getEventName()` was overridden change it to `getName()` or declare the event name as `protected const NAME`.
* Instead of overriding `getExchange()` and `getRoutingKey()` you must declare constants `EXCHANGE_NAME` and `ROUTING_KEY`
* Done! =)

P.S. add `implements  Chocofamilyme\LaravelPubSub\Events\DurableEvent` for classes extend `Chocofamilyme\LaravelPubSub\Events\PublishEvent` to persist event in database.
