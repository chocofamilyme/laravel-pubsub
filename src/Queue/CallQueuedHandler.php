<?php

declare(strict_types=1);

namespace Chocofamilyme\LaravelPubSub\Queue;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Queue\InteractsWithQueue;
use ReflectionClass;
use Illuminate\Contracts\Container\BindingResolutionException;
use Throwable;

/**
 * Class CallQueuedHandler
 *
 * @package Chocofamilyme\LaravelPubSub\Queue
 */
class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     */
    protected Dispatcher $dispatcher;

    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * Create a new handler instance.
     *
     * @param Dispatcher $dispatcher
     * @param Container $container
     */
    public function __construct(Dispatcher $dispatcher, Container $container)
    {
        $this->container  = $container;
        $this->dispatcher = $dispatcher;
    }

    public function call(Job $job, string $listener, array $data): void
    {
        try {
            $listener = $this->container->make($listener);
        } catch (BindingResolutionException $e) {
            $this->handleModelNotFound($job, $e);
        }

        $listener = $this->setJobInstanceIfNecessary($job, $listener);

        try {
            $this->dispatchThroughMiddleware($job, $listener, $data);
        } catch (Throwable $e) {
            if (method_exists($listener, 'failed')) {
                $listener->failed($data, $e);
            }
        }

        if (!$job->hasFailed() && !$job->isReleased()) {
            $this->ensureNextJobInChainIsDispatched($listener);
        }

        if (!$job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Dispatch the given job / command through its specified middleware.
     *
     * @param Job                             $job
     * @param class-string|object             $listener
     * @param                                 $data
     *
     * @return mixed
     */
    protected function dispatchThroughMiddleware(Job $job, $listener, $data)
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingCLosureReturnType
         * @psalm-suppress PossiblyInvalidMethodCall
         * @psalm-suppress PossiblyInvalidPropertyFetch
         */
        return (new Pipeline($this->container))->send($listener)
            ->through(
                array_merge(
                    method_exists($listener, 'middleware') ? $listener->middleware() : [],
                    $listener->middleware ?? []
                )
            )
            ->then(
                function ($listener) use ($data) {
                    return $listener->handle($data);
                }
            );
    }


    /**
     * Set the job instance of the given class if necessary.
     *
     * @param Job   $job
     * @param mixed $instance
     *
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Job $job, $instance)
    {
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Ensure the next job in the chain is dispatched if applicable.
     *
     * @param mixed $listener
     *
     * @return void
     */
    protected function ensureNextJobInChainIsDispatched($listener)
    {
        if (method_exists($listener, 'dispatchNextJobInChain')) {
            $listener->dispatchNextJobInChain();
        }
    }

    /**
     * Handle a model not found exception.
     *
     * @param Job       $job
     * @param Exception $e
     *
     * @return void
     */
    protected function handleModelNotFound(Job $job, $e)
    {
        /** @var class-string $class */
        $class = $job->resolveName();

        try {
            $shouldDelete = (new ReflectionClass($class))
                    ->getDefaultProperties()['deleteWhenMissingModels'] ?? false;
        } catch (Exception $e) {
            $shouldDelete = false;
        }

        if ($shouldDelete) {
            $job->delete();
        }

        $job->fail($e);
    }

    /**
     * Call the failed method on the job instance.
     *
     * The exception that caused the failure will be passed.
     *
     * @param array     $data
     * @param Exception $e
     *
     * @return void
     */
    public function failed(array $data, $e)
    {
        if (isset($data['command'])) {
            $command = \unserialize($data['command']);

            if (method_exists($command, 'failed')) {
                $command->failed($e);
            }
        }
    }
}
