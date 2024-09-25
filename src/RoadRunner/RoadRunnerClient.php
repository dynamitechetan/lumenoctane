<?php

namespace Twid\Octane\RoadRunner;

use Laravel\Lumen\Application;
use Illuminate\Http\Request;
use Twid\Octane\Contracts\Client;
use Twid\Octane\Contracts\StoppableClient;
use Twid\Octane\MarshalsPsr7RequestsAndResponses;
use Twid\Octane\Octane;
use Twid\Octane\OctaneResponse;
use Twid\Octane\RequestContext;
use Spiral\RoadRunner\Http\PSR7Worker;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RoadRunnerClient implements Client, StoppableClient
{
    use MarshalsPsr7RequestsAndResponses;

    public function __construct( PSR7Worker $client)
    {
        $this->client = $client;
    }

    /**
     * Marshal the given request context into an Illuminate request.
     *
     * @param  \Twid\Octane\RequestContext  $context
     * @return array
     */
    public function marshalRequest(RequestContext $context): array
    {
        return [
            $this->toHttpFoundationRequest($context->psr7Request),
            $context,
        ];
    }

    /**
     * Send the response to the server.
     *
     * @param  \Twid\Octane\RequestContext  $context
     * @param  \Twid\Octane\OctaneResponse  $octaneResponse
     * @return void
     */
    public function respond(RequestContext $context, OctaneResponse $octaneResponse): void
    {
        if ($octaneResponse->outputBuffer &&
            ! $octaneResponse->response instanceof StreamedResponse &&
            ! $octaneResponse->response instanceof BinaryFileResponse) {
            $octaneResponse->response->setContent(
                $octaneResponse->outputBuffer.$octaneResponse->response->getContent()
            );
        }

        $this->client->respond($this->toPsr7Response($octaneResponse->response));
    }

    /**
     * Send an error message to the server.
     *
     * @param  \Throwable  $e
     * @param  \Laravel\Lumen\Application  $app
     * @param  \Illuminate\Http\Request  $request
     * @param  \Twid\Octane\RequestContext  $context
     * @return void
     */
    public function error(Throwable $e, Application $app, Request $request, RequestContext $context): void
    {
        $this->client->getWorker()->error(Octane::formatExceptionForClient(
            $e,
            $app->make('config')->get('app.debug')
        ));
    }

    /**
     * Stop the underlying server / worker.
     *
     * @return void
     */
    public function stop(): void
    {
        $this->client->getWorker()->stop();
    }
}
