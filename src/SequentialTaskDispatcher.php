<?php

namespace Twid\Octane;

use Twid\Octane\Contracts\DispatchesTasks;
use Twid\Octane\Exceptions\TaskExceptionResult;
use Throwable;

class SequentialTaskDispatcher implements DispatchesTasks
{
    /**
     * Concurrently resolve the given callbacks via background tasks, returning the results.
     *
     * Results will be keyed by their given keys - if a task did not finish, the tasks value will be "false".
     *
     * @param  array  $tasks
     * @param  int  $waitMilliseconds
     * @return array
     *
     * @throws \Twid\Octane\Exceptions\TaskException
     * @throws \Twid\Octane\Exceptions\TaskTimeoutException
     */
    public function resolve(array $tasks, int $waitMilliseconds = 1): array
    {
        return collect($tasks)->mapWithKeys(
            fn ($task, $key) => [$key => (function () use ($task) {
                try {
                    return $task();
                } catch (Throwable $ex) {
                    report($ex);

                    return TaskExceptionResult::from($ex);
                }
            })()]
        )->each(function ($result) {
            if ($result instanceof TaskExceptionResult) {
                throw $result->getOriginal();
            }
        })->all();
    }

    /**
     * Concurrently dispatch the given callbacks via background tasks.
     *
     * @param  array  $tasks
     * @return void
     */
    public function dispatch(array $tasks): void
    {
        try {
            $this->resolve($tasks);
        } catch (Throwable $exception) {
            // ..
        }
    }
}
