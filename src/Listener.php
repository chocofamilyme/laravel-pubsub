<?php
/**
 * @package Chocolife.me
 * @author  Moldabayev Vadim <moldabayev.v@chocolife.kz>
 */

namespace Chocofamilyme\LaravelPubSub;

use Chocofamilyme\LaravelPubSub\Listeners\EventRouter;
use Chocofamilyme\LaravelPubSub\Queue\Listeners\RabbitMQListener;
use Illuminate\Queue\WorkerOptions;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use VladimirYuldashev\LaravelQueueRabbitMQ\Consumer;
use Chocofamilyme\LaravelPubSub\Queue\RabbitMQQueue;
use Chocofamilyme\LaravelPubSub\Queue\Factory\RabbitMQFactory;

/**
 * Class Listener
 * Потребляет сообщения которые приходят по маршрутом из RabbitMQ Exchanges
 *
 * @package Chocofamilyme\LaravelPubSub
 */
class Listener extends Consumer
{
    /**
     * @var string
     */
    protected $exchange = '';

    /**
     * @var string
     */
    protected $exchangeType = 'topic';

    /**
     * @var bool
     */
    protected $durable = true;

    /**
     * @var bool
     */
    protected $autoDelete = false;

    /**
     * @var bool
     */
    protected $exclusive = false;

    /**
     * @var array
     */
    protected $routes = [];

    /**
     * @var string
     */
    protected $job = 'RabbitMQListener';

    /**
     * @param string        $connectionName
     * @param string        $queue
     * @param WorkerOptions $options
     *
     * @throws \ErrorException
     */
    public function daemon($connectionName, $queue, WorkerOptions $options): void
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        /** @var RabbitMQQueue $connection */
        $connection = $this->manager->connection($connectionName);

        $this->channel = $connection->getChannel();

        $connection->declareQueue(
            $queue,
            $this->durable,
            $this->autoDelete,
            [
                'exclusive' => $this->exclusive,
            ]
        );

        $this->channel->basic_qos(
            $this->prefetchSize,
            $this->prefetchCount,
            null
        );

        if ($this->exchange) {
            $this->channel->exchange_declare(
                $this->exchange,
                $this->exchangeType
            );

            foreach ($this->routes as $route) {
                $this->channel->queue_bind(
                    $queue,
                    $this->exchange,
                    $route
                );
            }
        }

        $this->channel->basic_consume(
            $queue,
            $this->consumerTag,
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($connection, $options, $connectionName, $queue): void {
                $listener = RabbitMQFactory::make(
                    $this->job,
                    $this->container,
                    $connection,
                    $message,
                    $connectionName,
                    $queue
                );

                if ($this->supportsAsyncSignals()) {
                    $this->registerTimeoutHandler($listener, $options);
                }

                $this->runJob($listener, $connectionName, $options);
            }
        );

        while ($this->channel->is_consuming()) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (!$this->daemonShouldRun($options, $connectionName, $queue)) {
                $this->pauseWorker($options, $lastRestart);
                continue;
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            try {
                $this->channel->wait(null, true, (int) $options->timeout);
            } catch (AMQPRuntimeException $exception) {
                $this->exceptions->report($exception);

                $this->kill(1);
            } catch (Exception $exception) {
                $this->exceptions->report($exception);

                $this->stopWorkerIfLostConnection($exception);
            } catch (Throwable $exception) {
                $this->exceptions->report($exception = new FatalThrowableError($exception));

                $this->stopWorkerIfLostConnection($exception);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $this->stopIfNecessary($options, $lastRestart, null);
        }
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange(string $exchange): void
    {
        $this->exchange = $exchange;
    }

    /**
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param array $routes
     */
    public function setRoutes(array $routes): void
    {
        $this->routes = $routes;
    }

    /**
     * @return string
     */
    public function getExchangeType(): string
    {
        return $this->exchangeType;
    }

    /**
     * @param string $exchangeType
     */
    public function setExchangeType(string $exchangeType): void
    {
        $this->exchangeType = $exchangeType;
    }

    /**
     * @param bool $durable
     */
    public function setDurable(bool $durable): void
    {
        $this->durable = $durable;
    }

    /**
     * @param bool $autoDelete
     */
    public function setAutoDelete(bool $autoDelete): void
    {
        $this->autoDelete = $autoDelete;
    }

    /**
     * @param bool $exclusive
     */
    public function setExclusive(bool $exclusive): void
    {
        $this->exclusive = $exclusive;
    }

    /**
     * @param string $job
     */
    public function setJob(string $job): void
    {
        $this->job = $job;
    }
}
