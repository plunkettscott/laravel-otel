<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel as HttpKernelInterface;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use PlunkettScott\LaravelOpenTelemetry\Http\Middleware\TraceMiddleware;

class RequestWatcher extends Watcher
{
    /**
     * {@inheritDoc}
     *
     * @throws BindingResolutionException
     */
    public function register(Application $app): void
    {
        if (! $app->bound(HttpKernelInterface::class)) {
            // We attach to the HttpKernel, so we need it to be available.
            return;
        }

        /** @var HttpKernel $httpKernel */
        $httpKernel = $app->make(HttpKernelInterface::class);

        if (! ($httpKernel instanceof HttpKernel)) {
            // We only support the default HttpKernel implementation.
            return;
        }

        /** @var RequestWatcherOptions $requestWatcherOptions */
        $requestWatcherOptions = is_array($this->options)
            ? RequestWatcherOptions::fromArray($this->options)
            : $this->options;

        $app->singleton(TraceMiddleware::class, function ($app) use ($requestWatcherOptions) {
            return new TraceMiddleware($app->get(TracerProviderInterface::class), $requestWatcherOptions);
        });

        $httpKernel->prependMiddleware(TraceMiddleware::class);
    }
}
