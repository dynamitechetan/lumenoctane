<?php

namespace Twid\Octane\Swoole\Handlers;

use Twid\Octane\ApplicationFactory;
use Twid\Octane\Stream;
use Twid\Octane\Swoole\SwooleClient;
use Twid\Octane\Swoole\SwooleExtension;
use Twid\Octane\Swoole\WorkerState;
use Twid\Octane\Worker;
use Swoole\Http\Server;
use Throwable;

class OnWorkerStart
{
    public function __construct(
         SwooleExtension $extension,
         $basePath,
         array $serverState,
         WorkerState $workerState,
         bool $shouldSetProcessName = true
    ) {
        $this->extension = $extension;
        $this->basePath = $basePath;
        $this->serverState = $serverState;
        $this->workerState = $workerState;
        $this->shouldSetProcessName = $shouldSetProcessName;
    }

    /**
     * Handle the "workerstart" Swoole event.
     *
     * @param  \Swoole\Http\Server  $server
     * @param  int  $workerId
     * @return void
     */
    public function __invoke($server, int $workerId)
    {
        $this->clearOpcodeCache();

        $this->workerState->server = $server;
        $this->workerState->workerId = $workerId;
        $this->workerState->workerPid = posix_getpid();
        $this->workerState->worker = $this->bootWorker($server);

//        $this->dispatchServerTickTaskEverySecond($server);
//        $this->streamRequestsToConsole($server);

        if ($this->shouldSetProcessName) {
            $isTaskWorker = $workerId >= $server->setting['worker_num'];

            $this->extension->setProcessName(
                $this->serverState['appName'],
                $isTaskWorker ? 'task worker process' : 'worker process',
            );
        }
    }

    /**
     * Boot the Octane worker and application.
     *
     * @param  \Swoole\Http\Server  $server
     * @return \Twid\Octane\Worker
     */
    protected function bootWorker($server)
    {
        try {
            return tap(new Worker(
                new ApplicationFactory($this->basePath),
                $this->workerState->client = new SwooleClient
            ))->boot([
                'octane.cacheTable' => $this->workerState->cacheTable,
                Server::class => $server,
                WorkerState::class => $this->workerState,
            ]);
        } catch (Throwable $e) {
            Stream::shutdown($e);
            $server->shutdown();
        }
    }

    /**
     * Start the Octane server tick to dispatch the tick task every second.
     *
     * @param  \Swoole\Http\Server  $server
     * @return void
     */
    protected function dispatchServerTickTaskEverySecond($server)
    {
        // ...
    }

    /**
     * Register the request handled listener that will output request information per request.
     *
     * @param  \Swoole\Http\Server  $server
     * @return void
     */
    protected function streamRequestsToConsole($server)
    {
        $garbage = 10;
        if ((memory_get_usage() / 1024 / 1024) > $garbage) {
            gc_collect_cycles();
        }
//        $this->workerState->worker->onRequestHandled(function ($request, $response, $sandbox) {
//            echo  "request ---------> \n";
//            if (! $sandbox->environment('local', 'testing')) {
//                return;
//            }

//            Stream::request(
//                $request->getMethod(),
//                $request->fullUrl(),
//                $response->getStatusCode(),
//                (microtime(true) - $this->workerState->lastRequestTime) * 1000,
//            );
//        });
    }

    /**
     * Clear the APCu and Opcache caches.
     *
     * @return void
     */
    protected function clearOpcodeCache()
    {
        foreach (['apcu_clear_cache', 'opcache_reset'] as $function) {
            if (function_exists($function)) {
                $function();
            }
        }
    }
}
