<?php

namespace Twid\Octane\Concerns;

use Twid\Octane\Contracts\DispatchesTasks;
use Twid\Octane\SequentialTaskDispatcher;
use Twid\Octane\Swoole\ServerStateFile;
use Twid\Octane\Swoole\SwooleHttpTaskDispatcher;
use Twid\Octane\Swoole\SwooleTaskDispatcher;
use Swoole\Http\Server;

trait ProvidesConcurrencySupport
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
    public function concurrently(array $tasks, int $waitMilliseconds = 3000)
    {
        return $this->tasks()->resolve($tasks, $waitMilliseconds);
    }

    /**
     * Get the task dispatcher.
     *
     * @return \Twid\Octane\Contracts\DispatchesTasks
     */
    public function tasks()
    {
//        return match (true) {
//            app()->bound(DispatchesTasks::class) => app(DispatchesTasks::class),
//            app()->bound(Server::class) => new SwooleTaskDispatcher,
//            class_exists(Server::class) => (fn (array $serverState) => new SwooleHttpTaskDispatcher(
//                $serverState['state']['host'] ?? '127.0.0.1',
//                $serverState['state']['port'] ?? '8000',
//                new SequentialTaskDispatcher
//            ))(app(ServerStateFile::class)->read()),
//            default => new SequentialTaskDispatcher,
//        };

        return  new SequentialTaskDispatcher;
    }
}
