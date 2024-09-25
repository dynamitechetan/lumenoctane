<?php

namespace Twid\Octane\Swoole;

use Closure;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Twid\Octane\Contracts\DispatchesTasks;
use Twid\Octane\Exceptions\TaskExceptionResult;
use Twid\Octane\Exceptions\TaskTimeoutException;
use Laravel\SerializableClosure\SerializableClosure;

class SwooleHttpTaskDispatcher implements DispatchesTasks
{
    public function __construct(
         string $host,
         string $port,
         DispatchesTasks $fallbackDispatcher
    ) {
    }

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
    public function resolve(array $tasks, int $waitMilliseconds = 3000): array
    {
        $tasks = collect($tasks)->mapWithKeys(function ($task, $key) {
            return [$key => $task instanceof Closure
                            ? new SerializableClosure($task)
                            : $task, ];
        })->all();

        try {
            $response = Http::timeout(($waitMilliseconds / 1000) + 5)->post("http://{$this->host}:{$this->port}/octane/resolve-tasks", [
                'tasks' => Crypt::encryptString(serialize($tasks)),
                'wait' => $waitMilliseconds,
            ]);
            if ($response->status() == 200) {
                return unserialize($response);
            } elseif ($response->status() == 504) {
                throw TaskTimeoutException::after($waitMilliseconds);
            } else {
                throw TaskExceptionResult::from(
                    new Exception('Invalid response from task server.'),
                )->getOriginal();
            }

//            return match ($response->status()) {
//                200 => unserialize($response),
//                504 => throw TaskTimeoutException::after($waitMilliseconds),
//                default => throw TaskExceptionResult::from(
//                    new Exception('Invalid response from task server.'),
//                )->getOriginal(),
//            };
        } catch (ConnectionException $exception) {
            return $this->fallbackDispatcher->resolve($tasks, $waitMilliseconds);
        }
    }

    /**
     * Concurrently dispatch the given callbacks via background tasks.
     *
     * @param  array  $tasks
     * @return void
     */
    public function dispatch(array $tasks): void
    {
        $tasks = collect($tasks)->mapWithKeys(function ($task, $key) {
            return [$key => $task instanceof Closure
                            ? new SerializableClosure($task)
                            : $task, ];
        })->all();

        try {
            Http::post("http://{$this->host}:{$this->port}/octane/dispatch-tasks", [
                'tasks' => Crypt::encryptString(serialize($tasks)),
            ]);
        } catch (ConnectionException $exception) {
            $this->fallbackDispatcher->dispatch($tasks);
        }
    }
}
