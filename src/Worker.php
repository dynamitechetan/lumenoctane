<?php

namespace Twid\Octane;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Laravel\Lumen\Application;
use Twid\Octane\Contracts\Client;
use Twid\Octane\Contracts\ServesStaticFiles;
use Twid\Octane\Contracts\Worker as WorkerContract;
use Twid\Octane\Events\TaskReceived;
use Twid\Octane\Events\TaskTerminated;
use Twid\Octane\Events\TickReceived;
use Twid\Octane\Events\TickTerminated;
use Twid\Octane\Events\WorkerErrorOccurred;
use Twid\Octane\Events\WorkerStarting;
use Twid\Octane\Events\WorkerStopping;
use Twid\Octane\Exceptions\TaskExceptionResult;
use Twid\Octane\Swoole\TaskResult;
use RuntimeException;
use Throwable;

class Worker implements WorkerContract
{
    use DispatchesEvents;

    protected $requestHandledCallbacks = [];

    /**
     * The root application instance.
     *
     * @var Application
     */
    protected $app;

    public function __construct(
         ApplicationFactory $appFactory,
         Client $client
    ) {
        $this->appFactory = $appFactory;
        $this->client = $client;
    }

    /**
     * Boot / initialize the Octane worker.
     *
     * @param  array  $initialInstances
     * @return void
     */
    public function boot(array $initialInstances = []): void
    {
        // First we will create an instance of the Laravel application that can serve as
        // the base container instance we will clone from on every request. This will
        // also perform the initial bootstrapping that's required by the framework.
        $this->app = $app = $this->appFactory->createApplication(
            array_merge(
                $initialInstances,
                [Client::class => $this->client],
            )
        );

//        $this->dispatchEvent($app, new WorkerStarting($app));
    }

    /**
     * Handle an incoming request and send the response to the client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Twid\Octane\RequestContext  $context
     * @return void
     */
    public function handle(Request $request, RequestContext $context): void
    {
        if ($this->client instanceof ServesStaticFiles &&
            $this->client->canServeRequestAsStaticFile($request, $context)) {
            $this->client->serveStaticFile($request, $context);

            return;
        }

        // We will clone the application instance so that we have a clean copy to switch
        // back to once the request has been handled. This allows us to easily delete
        // certain instances that got resolved / mutated during a previous request.
        CurrentApplication::set($sandbox = clone $this->app);

        $gateway = new ApplicationGateway($this->app, $sandbox);

        try {
            $responded = false;

            ob_start();

            $response = $gateway->handle($request);

            $output = ob_get_contents();

            ob_end_clean();

            // Here we will actually hand the incoming request to the Laravel application so
            // it can generate a response. We'll send this response back to the client so
            // it can be returned to a browser. This gateway will also dispatch events.
            $this->client->respond(
                $context,
                $octaneResponse = new OctaneResponse($response, $output),
            );

            $responded = true;

            $this->invokeRequestHandledCallbacks($request, $response, $sandbox);

//            $gateway->terminate($request, $response);
        } catch (Throwable $e) {
            $this->handleWorkerError($e, $sandbox, $request, $context, $responded);
        } finally {
            $sandbox->flush();

//            $this->app->make('view.engine.resolver')->forget('blade');
//            $this->app->make('view.engine.resolver')->forget('php');

            // After the request handling process has completed we will unset some variables
            // plus reset the current application state back to its original state before
            // it was cloned. Then we will be ready for the next worker iteration loop.
            unset($gateway, $sandbox, $request, $response, $octaneResponse, $output);

            CurrentApplication::set($this->app);
        }
    }

    /**
     * Handle an incoming task.
     *
     * @param  mixed  $data
     * @return mixed
     */
    public function handleTask($data)
    {
        $result = false;

        // We will clone the application instance so that we have a clean copy to switch
        // back to once the request has been handled. This allows us to easily delete
        // certain instances that got resolved / mutated during a previous request.
        CurrentApplication::set($sandbox = clone $this->app);

        try {
//            $this->dispatchEvent($sandbox, new TaskReceived($this->app, $sandbox, $data));

            $result = $data();

//            $this->dispatchEvent($sandbox, new TaskTerminated($this->app, $sandbox, $data, $result));
        } catch (Throwable $e) {
//            $this->dispatchEvent($sandbox, new WorkerErrorOccurred($e, $sandbox));

            return TaskExceptionResult::from($e);
        } finally {
            $sandbox->flush();

            // After the request handling process has completed we will unset some variables
            // plus reset the current application state back to its original state before
            // it was cloned. Then we will be ready for the next worker iteration loop.
            unset($sandbox);

            CurrentApplication::set($this->app);
        }

        return new TaskResult($result);
    }

    /**
     * Handle an incoming tick.
     *
     * @return void
     */
    public function handleTick(): void
    {
        CurrentApplication::set($sandbox = clone $this->app);

        try {
//            $this->dispatchEvent($sandbox, new TickReceived($this->app, $sandbox));
//            $this->dispatchEvent($sandbox, new TickTerminated($this->app, $sandbox));
        } catch (Throwable $e) {
//            $this->dispatchEvent($sandbox, new WorkerErrorOccurred($e, $sandbox));
        } finally {
            $sandbox->flush();

            unset($sandbox);

            CurrentApplication::set($this->app);
        }
    }

    /**
     * Handle an uncaught exception from the worker.
     *
     * @param  \Throwable  $e
     * @param  \Laravel\Lumen\Application  $app
     * @param  \Illuminate\Http\Request  $request
     * @param  \Twid\Octane\RequestContext  $context
     * @param  bool  $hasResponded
     * @return void
     */
    protected function handleWorkerError(
        Throwable $e,
        Application $app,
        Request $request,
        RequestContext $context,
        bool $hasResponded
    ): void {
        if (! $hasResponded) {
            $this->client->error($e, $app, $request, $context);
        }

//        $this->dispatchEvent($app, new WorkerErrorOccurred($e, $app));
    }

    /**
     * Invoke the request handled callbacks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \Laravel\Lumen\Application  $sandbox
     * @return void
     */
    protected function invokeRequestHandledCallbacks($request, $response, $sandbox): void
    {
        foreach ($this->requestHandledCallbacks as $callback) {
            $callback($request, $response, $sandbox);
        }
    }

    /**
     * Register a closure to be invoked when requests are handled.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function onRequestHandled(Closure $callback)
    {
        $this->requestHandledCallbacks[] = $callback;

        return $this;
    }

    /**
     * Get the application instance being used by the worker.
     *
     * @return \Laravel\Lumen\Application
     */
    public function application(): Application
    {
        if (! $this->app) {
            throw new RuntimeException('Worker has not booted. Unable to access application.');
        }

        return $this->app;
    }

    /**
     * Terminate the worker.
     *
     * @return void
     */
    public function terminate(): void
    {
//        $this->dispatchEvent($this->app, new WorkerStopping($this->app));
    }
}
