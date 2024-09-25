<?php

namespace Twid\Octane\Listeners;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Twid\Octane\Exceptions\DdException;
use Twid\Octane\Stream;

class ReportException
{
    /**
     * Handle the event.
     *
     * @param  mixed  $event
     * @return void
     */
    public function handle($event): void
    {
        if ($event->exception) {
            tap($event->sandbox, function ($sandbox) use ($event) {
                if ($event->exception instanceof DdException) {
                    return;
                }

                if ($sandbox->environment('local', 'testing')) {
                    Stream::throwable($event->exception);
                }

                $sandbox[ExceptionHandler::class]->report($event->exception);
            });
        }
    }
}
