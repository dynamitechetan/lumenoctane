<?php

namespace Twid\Octane\Events;

use Laravel\Lumen\Application;
use Twid\Octane\Contracts\OperationTerminated;

class TickTerminated implements OperationTerminated
{
    use HasApplicationAndSandbox;

    public $app;
    public $sandbox;

    public function __construct(
         Application $app,
         Application $sandbox
    ) {
        $this->app = $app;
        $this->sandbox = $sandbox;
    }
}
