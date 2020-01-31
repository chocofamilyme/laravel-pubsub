# Laravel PubSub Library
Laravel pub/sub library allows you to publish and consume and process rabbit events.

# Installation
```bash
composer require chocofamilyme/laravel-pubsub
```

# Publishing the configuration (optional)
```bash
php artisan vendor:publish --provider="Chocofamilyme\LaravelPubSub\Providers\PubSubServiceProvider"
```

# Configuration
## AMQP
AMQP configuration file is located under config/queue.php and contains configuration for RabbitMQ.

#### Таблица параметров

| Ключ                              | Значение                  | Описание  |
| --------------------------------- |:------------------------- | :---------|
| connection                        | По умолчанию PhpAmqpLib\Connection\AMQPLazyConnection::class | [php-amqplib](https://github.com/php-amqplib/php-amqplib/tree/master/PhpAmqpLib/Connection) |
| options                           | Array                     | Смотри - [php-amqplib](https://github.com/php-amqplib/php-amqplib)  |
| options.message-ttl               | милисекунды               | Время жизни сообщений в очереди в милисекундах  |
| options.publisher.queue           | Array                     | Настройки для публикатора  |
| options.publisher.queue.declare   | false                     | Нужно ли создать очередь перед публикацией  |
| options.publisher.queue.bind      | false                     | Нужно ли связать очередь с exchange перед публикацией  |
| options.publisher.exchange.declare | false                    | Нужно ли создать exchange перед публикацией  |
| options.publisher.exchange.name   | string                    | Имя exchange |

## PubSub
PubSub configuration file is located under config/pubsub.php and contains configuration for EventRouting.
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Listen for events
    |--------------------------------------------------------------------------
    |
    | Define event name and it's listeners. Please notice that one event name may have multiple listeners
    |
    | Example:
    |
    | 'user.notified' => [
    |     NotifyAboutDeviceChangeListener::class,
    | ],
    |
    */
    'listen' => [
    ]
];
```

## Usage
### Single event
```bash
php artisan event:listen gateway.user.authenticated --job=common
```
Will listen to single event "gateway.user.authenticated" in default exchange and queue name

### Wildcard event
```bash
php artisan event:listen gateway.user.# --exchange=gateway --queue=guardqueue --job=common
```
Will listen to all "gateway.user.*" events in exchange gateway and with queue name "guardqueue"

### Laravel event
```bash
php artisan event:listen
```


#### Таблица параметров для демона Listener

| Ключ                              | Значение                  | Описание  |
| --------------------------------- |:------------------------- | :---------|
| connection                        | По умолчанию rabbitmq     | Драйвер из config/queue.php |
| queue                             | По умолчанию default      |  Имя очереди для consumer  |
| exchange                          | ''                        |  exchange с котором связать очередь  |
| exchange_type                     | По умолчанию topic        |  [RabbitMQ Doc](https://www.rabbitmq.com/tutorials/amqp-concepts.html)) |
| once                              |                           |  Only process the next job on the queue |
| job                               | laravel|common            |  По умолчанию laravel. Какие сообщения обрабатывет consumer. laravel - созданные фреймворком laravel через Events |
| stop-when-empty                   | 0                         |  Stop when the queue is empty |
| delay                             | 0                         |  The number of seconds to delay failed jobs |
| force                             | 0                         |  Force the worker to run even in maintenance mode |
| memory                            | 128                       |  The memory limit in megabytes |
| sleep                             | 3                         |  Number of seconds to sleep when no job is available |
| timeout                           | 0                         |  Максимальное время ожидания до получения первого сообщения  |
| tries                             | 1                         |  Number of times to attempt a job before logging it failed  |
| exclusive                         | 0                         |   used by only one connection and the queue will be deleted when that connection close  |
| consumer_exclusive                | 0                         |   request exclusive consumer access, meaning only this consumer can access the queue  |
| consumer-tag                      | ''                        |    |
| prefetch-size                     | 0                         |    |
| prefetch-count                    | 1                         | [RabbitMQ Doc](https://www.rabbitmq.com/consumer-prefetch.html)  |

### Base publish message

```php
 Amqp::publish('route.test', ['bodyKey' => 'bodyValue'], [
        'exchange' => [
            'name' => 'test',
            'type' => 'topic',
        ],
        'headers' => [
            'application_headers' => [
                'headerKey' => 'headerValue'
            ],
            'message_id' => 'uuid4',
        ],
    ]
);
```
