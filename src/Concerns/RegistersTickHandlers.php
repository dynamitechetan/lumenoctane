<?php

namespace Twid\Octane\Concerns;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Cache;
use Twid\Octane\Events\TickReceived;
use Twid\Octane\Swoole\InvokeTickCallable;

trait RegistersTickHandlers
{
    /**
     * Register a callback to be called every N seconds.
     *
     * @param  string  $key
     * @param  callable  $callback
     * @param  int  $seconds
     * @param  bool  $immediate
     * @return \Twid\Octane\Swoole\InvokeTickCallable
     */
    public function tick(string $key, callable $callback, int $seconds = 1, bool $immediate = true)
    {
        $listener = new InvokeTickCallable(
            $key,
            $callback,
            $seconds,
            $immediate,
            Cache::store('octane'),
            app(ExceptionHandler::class)
        );

        app(Dispatcher::class)->listen(
            TickReceived::class,
            $listener
        );

        return $listener;
    }
}
