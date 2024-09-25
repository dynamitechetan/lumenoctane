<?php

namespace Twid\Octane\Commands;

use Twid\Octane\RoadRunner\ServerProcessInspector as RoadRunnerServerProcessInspector;
use Twid\Octane\Swoole\ServerProcessInspector as SwooleServerProcessInspector;

class StatusCommand extends Command
{
    /**
     * The command's signature.
     *
     * @var string
     */
    public $signature = 'octane:status {--server= : The server that is running the application}';

    /**
     * The command's description.
     *
     * @var string
     */
    public $description = 'Get the current status of the Octane server';

    /**
     * Handle the command.
     *
     * @return int
     */
    public function handle()
    {
        $server = $this->option('server') ?: config('octane.server');

        if (!in_array($server, ['swoole', 'roadrunner'])) {
            $this->invalidServer($server);
            return false;
        }

        $isRunning =  $server == 'swoole' ? $this->isSwooleServerRunning() :
            $this->isRoadRunnerServerRunning();

        return ! tap($isRunning, function ($isRunning) {
            $isRunning
                ? $this->info('Octane server is running.')
                : $this->info('Octane server is not running.');
        });
    }

    /**
     * Check if the Swoole server is running.
     *
     * @return bool
     */
    protected function isSwooleServerRunning()
    {
        return app(SwooleServerProcessInspector::class)
            ->serverIsRunning();
    }

    /**
     * Check if the RoadRunner server is running.
     *
     * @return bool
     */
    protected function isRoadRunnerServerRunning()
    {
        return app(RoadRunnerServerProcessInspector::class)
            ->serverIsRunning();
    }

    /**
     * Inform the user that the server type is invalid.
     *
     * @param  string  $server
     * @return bool
     */
    protected function invalidServer(string $server)
    {
        $this->error("Invalid server: {$server}.");

        return false;
    }
}
