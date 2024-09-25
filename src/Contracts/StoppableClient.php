<?php

namespace Twid\Octane\Contracts;

interface StoppableClient extends Client
{
    /**
     * Stop the underlying server / worker.
     *
     * @return void
     */
    public function stop(): void;
}
