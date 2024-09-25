<?php

namespace Twid\Octane\Events;

use Laravel\Lumen\Application;

class WorkerStopping
{
    public $app;
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
