<?php

namespace Twid\Octane\Contracts;

use Illuminate\Http\Request;
use Twid\Octane\RequestContext;

interface ServesStaticFiles
{
    /**
     * Determine if the request can be served as a static file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Twid\Octane\RequestContext  $context
     * @return bool
     */
    public function canServeRequestAsStaticFile(Request $request, RequestContext $context): bool;

    /**
     * Serve the static file that was requested.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Twid\Octane\RequestContext  $context
     * @return void
     */
    public function serveStaticFile(Request $request, RequestContext $context): void;
}
