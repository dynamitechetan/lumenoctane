<?php

namespace Twid\Octane\Swoole\Handlers;

use Twid\Octane\Swoole\Actions\EnsureRequestsDontExceedMaxExecutionTime;
use Twid\Octane\Swoole\ServerStateFile;
use Twid\Octane\Swoole\SwooleExtension;
use Swoole\Timer;

class OnServerStart
{
    public function __construct(
         ServerStateFile $serverStateFile,
         SwooleExtension $extension,
         string $appName,
         int $maxExecutionTime,
         $timerTable,
         bool $shouldTick = true,
         bool $shouldSetProcessName = true
    ) {
        $this->serverStateFile = $serverStateFile;
        $this->extension = $extension;
        $this->appName = $appName;
        $this->maxExecutionTime = $maxExecutionTime;
        $this->timerTable = $timerTable;
        $this->shouldTick = $shouldTick;
        $this->shouldSetProcessName = $shouldSetProcessName;
    }

    /**
     * Handle the "start" Swoole event.
     *
     * @param  \Swoole\Http\Server  $server
     * @return void
     */
    public function __invoke($server)
    {
        $this->serverStateFile->writeProcessIds(
            $server->master_pid,
            $server->manager_pid
        );

        if ($this->shouldSetProcessName) {
            $this->extension->setProcessName($this->appName, 'master process');
        }

        if ($this->shouldTick) {
            Timer::tick(1000, function () use ($server) {
                $server->task('octane-tick');
            });
        }

        if ($this->maxExecutionTime > 0) {
            Timer::tick(1000, function () use ($server) {
                (new EnsureRequestsDontExceedMaxExecutionTime(
                    $this->extension, $this->timerTable, $this->maxExecutionTime, $server
                ))();
            });
        }
    }
}
