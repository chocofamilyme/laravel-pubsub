# Laravel PubSub Library  
Laravel pub/sub library allows you to publish, consume and process rabbit events.
  
# Installation  
```bash  
composer require chocofamilyme/laravel-pubsub
```

# Upgrade v3 -> v4
Read upgrade guide [here](https://github.com/chocofamilyme/laravel-pubsub/blob/master/docs/UPGRADE.md)

# Publishing the configuration and migration  
```bash  
php artisan vendor:publish --provider="Chocofamilyme\LaravelPubSub\Providers\PubSubServiceProvider"
```
  
# Configurations
## AMQP (RabbitMQ) configuration
AMQP configuration should be inserted into config/queue.php

```php
'sync' => [  
...
],  
  
'database' => [  
...
],  
  
'beanstalkd' => [  
...
],

// Insert into your config/queue.php
'rabbitmq' => [  
    'driver' => 'rabbitmq',  
    'queue' => env('RABBITMQ_QUEUE', 'default'),  
    'connection' => PhpAmqpLib\Connection\AMQPSocketConnection::class,  
    'worker' => env('RABBITMQ_WORKER', Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue::class),  
  
    'hosts' => [  
        [
        'host' => env('SERVICE_RABBITMQ_HOST', '127.0.0.1'),  
        'port' => env('SERVICE_RABBITMQ_PORT', 5672),  
        'user' => env('SERVICE_RABBITMQ_USER', 'guest'),  
        'password' => env('SERVICE_RABBITMQ_PASSWORD', 'guest'),  
        'vhost' => env('SERVICE_RABBITMQ_VHOST', '/'),  
        ],
     ],  
  
  'options' => [  
      'ssl_options' => [  
      'cafile' => env('RABBITMQ_SSL_CAFILE', null),  
      'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),  
      'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),  
      'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),  
      'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),  
  ],
    
  'heartbeat' => 60,  
  'message-ttl' => 60000000,  
  
  'publisher' => [  
      'queue' => [  
          'declare' => false,  
          'bind' => false,
       ],  
      'exchange' => [  
          'declare' => false,
          'name' => 'exchange-name',  
      ],  
  ],
]
```

### Params
| Key                              | Value                  | Description  |  
| --------------------------------- |:------------------------- | :---------|  
| connection                        | Default PhpAmqpLib\Connection\AMQPLazyConnection::class | [php-amqplib](https://github.com/php-amqplib/php-amqplib/tree/master/PhpAmqpLib/Connection) |  
| options                           | Array                     | See - [php-amqplib](https://github.com/php-amqplib/php-amqplib)  |  
| options.message-ttl               | miliseconds               | Message life time  |  
| options.publisher.queue           | Array                     | Publisher config  |  
| options.publisher.queue.declare   | false                     | Should create queue before publishing  |  
| options.publisher.queue.bind      | false                     | Should bind queue with exchange before publishing |  
| options.publisher.exchange.declare | false                    | Should created exchange before publishing |  
| options.publisher.exchange.name   | string                    | Exchange name |
  
## Event routing configuration
Event routing configuration file is located under config/pubsub.php and contains configuration for EventRouting and storing events in database.  
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
    | listen => [
    |     'UserNotified' => [
    |         NotifyAboutDeviceChangeListener::class,
    |     ]
    | ],
    |
    */
    'listen' => [

    ],
    
    /**
     * Define database tables for storing data (publishing events, incoming events, etc.)
     */
    'tables' => [
        'events' => 'pubsub_events'
    ]
];  
```  
  
## Usage
You can listen for RabbitMQ events and publish them.

--job=laravel flag should be used if you want to listen to inter project events but from rabbitmq queue.
```bash
php artisan event:listen --job=laravel
```

--job=laravel flag should be used for intermicroservice communictaion.
```bash
php artisan event:listen --job=external
```

### Examples
#### Single event
```bash
php artisan event:listen gateway.user.authenticated --job=external
```  
Will listen to single event "gateway.user.authenticated" in default exchange and queue name. Configure the internal event routing in ```config/pubsub.php```
Event is taken from payload, when you publish the event it appends the event name automatically there.
  
#### Wildcard event
```bash
php artisan event:listen gateway.user.# --exchange=gateway --queue=guardqueue --job=external
```  
Will listen to all "gateway.user.*" events in exchange gateway and with queue name "guardqueue"  
  
#### Interproject communictaion
```bash  
php artisan event:listen
```  
Will listen for default laravel event, in the default case --job=laravel is set by default

  
#### php artisan event:listen flags and parameters
```
connection=rabbitmq                        : The name of the queue connection to work
--queue=                                   : The names of the queues to work
--exchange=                                : Optional, specifies exchange which should be listened [for default value see app/config/queue.php]
--exchange_type=topic                      : Optional, specifies exchange which should be listened [for default value see app/config/queue.php] [RabbitMQ Doc](https://www.rabbitmq.com/tutorials/amqp-concepts.html)
--once                                     : Only process the next job on the queue
--job=laravel                              : Handler for internal or external message
--stop-when-empty                          : Stop when the queue is empty
--delay=0                                  : The number of seconds to delay failed jobs
--force                                    : Force the worker to run even in maintenance mode
--memory=128                               : The memory limit in megabytes
--sleep=3                                  : Number of seconds to sleep when no job is available
--timeout=0                                : The number of seconds a child process can run
--tries=1                                  : Number of times to attempt a job before logging it failed
--exclusive=0                              : used by only one connection and the queue will be deleted when that connection close
--consumer_exclusive=0                     : request exclusive consumer access, meaning only this consumer can access the queue
--wait_non_blocking=0                      : non-blocking actions
--exchange_passive=0                       : If set, the server will reply with Declare-Ok if the exchange already exists with the same name, and raise an error if not [RabbitMQ Doc](https://www.rabbitmq.com/amqp-0-9-1-reference.html#exchange.declare.passive)
--exchange_durable=1                       : If set when creating a new exchange, the exchange will be marked as durable [RabbitMQ Doc](https://www.rabbitmq.com/amqp-0-9-1-reference.html#exchange.declare.durable)
--exchange_auto_delete=0                   : If set, the exchange is deleted when all queues have finished using it [RabbitMQ Doc](https://www.rabbitmq.com/amqp-0-9-1-reference.html#exchange.declare.auto-delete)
--consumer-tag                             :
--prefetch-size=0                          :
--prefetch-count=1                         : [RabbitMQ Doc](https://www.rabbitmq.com/consumer-prefetch.html)  |
```  
  
## How to publish events
### For laravel default way see the laravel documentation
https://laravel.com/

### If you want to publish event to RabbitMQ
We've tried to make it easy as possible for you, see how it works:
1. Create event with ```php artisan make:event```, please aware that the name of your event class will be the event name in the message payload.
It is then used for internal router ```config/pubsub.php``` 
2. Open fresh created event and extends from ```Chocofamilyme\LaravelPubSub\Events\PublishEvent```
3. You will have to override values of constants couple of methods like ```EXCHANGE_NAME```, ```NAME```, ```ROUTING_KEY```. These constants tell the dispatcher
which exchange should be used for this event and which routing key and name of the event. See? It's pretty self-descriptive.
4. Since you extended from ```PublishEvent``` class you could override more methods which could make the event more precise, for
that please see inside this class.
5. After our event is ready, we now can publish it in laravel way:
```php
event(new UserUpdatedEvent(1, 'Josh'));
```
Since this event extends from PublishEvent class implementing SendToRabbitMQInterface, it will be sent into rabbitmq automatically.

PS: Please note that you need to override toPayload() method returning array that would be used as message payload.

#### Example event class
```php
<?php

namespace App\Events;

use Chocofamilyme\LaravelPubSub\Events\PublishEvent;

class UserUpdatedEvent extends PublishEvent
{
    public int $id;
    public string $name;

    public const EXCHANGE_NAME = 'exchangeName';
    public const ROUTING_KEY   = 'user.updated';

    /**
     * Create a new event instance.
     *
     * @param int $id
     * @param string $name
     */
    public function __construct(int $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
```
