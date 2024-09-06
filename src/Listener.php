<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub;

use ErrorException;
use Exception;
use Illuminate\Queue\WorkerOptions;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
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
    protected string $exchange = '';
    protected string $exchangeType = 'topic';
    protected bool $durable = true;
    protected bool $autoDelete = false;
    protected bool $exclusive = false;
    protected array $routes = [];
    protected string $job = 'laravel';
    protected int $messageTtl = 0;
    protected bool $noAck = false;
    protected bool $consumerExclusive = false;
    protected bool $waitNonBlocking = true;
    private bool $exchangePassive = false;
    private bool $exchangeDurable = true;
    private bool $exchangeAutoDelete = false;

    /**
     * @param string        $connectionName
     * @param string        $queue
     * @param WorkerOptions $options
     *
     * @return int
     *
     * @psalm-suppress ImplementedReturnTypeMismatch
     * @throws Throwable
     */
    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        if ($this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $lastRestart = $this->getTimestampOfLastQueueRestart();

        [$startTime, $jobsProcessed] = [hrtime(true) / 1e9, 0];

        /** @var RabbitMQQueue $connection */
        $connection = $this->manager->connection($connectionName);

        $this->channel = $connection->getChannel();

        $connection->declareQueue(
            $queue,
            $this->durable,
            $this->autoDelete,
            $this->getQueueArguments()
        );

        $this->channel->basic_qos(
            $this->prefetchSize,
            $this->prefetchCount,
            false
        );

        if ($this->exchange) {
            $this->channel->exchange_declare(
                $this->exchange,
                $this->exchangeType,
                $this->exchangePassive,
                $this->exchangeDurable,
                $this->exchangeAutoDelete
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
            $this->noAck,
            $this->consumerExclusive,
            false,
            function (AMQPMessage $message) use ($connection, $options, $connectionName, $queue): void {
                $job = RabbitMQFactory::make(
                    $this->job,
                    $this->container,
                    $connection,
                    $message,
                    $connectionName,
                    $queue
                );

                $this->currentJob = $job;

                if ($this->supportsAsyncSignals()) {
                    $this->registerTimeoutHandler($job, $options);
                }

                $this->runJob($job, $connectionName, $options);

                if ($this->supportsAsyncSignals()) {
                    $this->resetTimeoutHandler();
                }
            }
        );

        while ($this->channel->is_consuming()) {
            // Before reserving any jobs, we will make sure this queue is not paused and
            // if it is we will just pause this worker for a given amount of time and
            // make sure we do not need to kill this worker process off completely.
            if (!$this->daemonShouldRun($options, $connectionName, $queue)) {
                /** @psalm-suppress PossiblyNullArgument */
                $this->pauseWorker($options, $lastRestart);
                continue;
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            try {
                $this->channel->wait(
                    null,
                    $this->waitNonBlocking,
                    (int)$options->timeout
                );
            } catch (AMQPRuntimeException $exception) {
                $this->exceptions->report($exception);

                $this->kill(1);
            } catch (Exception $exception) {
                $this->exceptions->report($exception);

                $this->stopWorkerIfLostConnection($exception);
            } catch (Throwable $exception) {
                $this->exceptions->report($exception = new ErrorException((string)$exception));

                $this->stopWorkerIfLostConnection($exception);
            }

            // If no job is got off the queue, we will need to sleep the worker.
            if ($this->currentJob === null) {
                $this->sleep($options->sleep);
            }

            // Finally, we will check to see if we have exceeded our memory limits or if
            // the queue should restart based on other indications. If so, we'll stop
            // this worker and let whatever is "monitoring" it restart the process.
            $status = $this->stopIfNecessary(
                $options,
                $lastRestart,
                $startTime,
                $jobsProcessed,
                $this->currentJob
            );

            if (! is_null($status)) {
                return $this->stop($status, $options);
            }

            $this->currentJob = null;
        }
    }

    /**
     * @return array
     */
    protected function getQueueArguments(): array
    {
        $arguments = [
            'exclusive' => $this->exclusive,
        ];

        if ($this->messageTtl) {
            $arguments['x-message-ttl'] = $this->messageTtl;
        }

        return $arguments;
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

    /**
     * @param int $messageTtl
     */
    public function setMessageTtl(int $messageTtl): void
    {
        $this->messageTtl = $messageTtl;
    }

    /**
     * @param bool $consumerExclusive
     */
    public function setConsumerExclusive(bool $consumerExclusive): void
    {
        $this->consumerExclusive = $consumerExclusive;
    }

    /**
     * @param bool $waitNonBlocking
     */
    public function setWaitNonBlocking(bool $waitNonBlocking): void
    {
        $this->waitNonBlocking = $waitNonBlocking;
    }

    /**
     * @param bool $exchangePassive
     */
    public function setExchangePassive(bool $exchangePassive): void
    {
        $this->exchangePassive = $exchangePassive;
    }

    /**
     * @param bool $exchangeDurable
     */
    public function setExchangeDurable(bool $exchangeDurable): void
    {
        $this->exchangeDurable = $exchangeDurable;
    }

    /**
     * @param bool $exchangeAutoDelete
     */
    public function setExchangeAutoDelete(bool $exchangeAutoDelete): void
    {
        $this->exchangeAutoDelete = $exchangeAutoDelete;
    }
}
