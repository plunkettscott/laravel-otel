<?php

namespace PlunkettScott\LaravelOpenTelemetry\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use PlunkettScott\LaravelOpenTelemetry\Concerns;
use PlunkettScott\LaravelOpenTelemetry\Watchers\RequestWatcherOptions;

class TraceMiddleware
{
    use Concerns\TracesHttpRequests,
        Concerns\AttachesTraceToLogContext;

    private TracerInterface $tracer;

    private RequestWatcherOptions $options;

    public function __construct(
        TracerProviderInterface $tracerProvider,
        RequestWatcherOptions $options,
    ) {
        $this->tracer = $tracerProvider->getTracer('plunkettscott/laravel-otel');
        $this->options = $options;
    }

    /**
     * @throws Exception
     */
    public function handle(Request $request, Closure $next)
    {
        // Create and activate the root span
        $this->createAndActivateRootSpan($this->options->continue_trace, $request);

        // Add the trace information to all messages logged within this request
        $this->attachTraceToLogContext();

        try {
            $response = $next($request);
        } catch (Exception $e) {
            // Record the exception on the root span
            $this->span->recordException($e);
            throw $e;
        }

        // Finally, return the response
        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->terminateRootSpan($request, $response);
    }
}
