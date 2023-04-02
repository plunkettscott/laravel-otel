<?php

namespace PlunkettScott\LaravelOpenTelemetry;

use Illuminate\Foundation\Application;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use PlunkettScott\LaravelOpenTelemetry\Resolvers\Contracts\UserResolver;
use Throwable;

class Otel
{
    use Concerns\RegistersWatchers,
        Concerns\InteractsWithCurrentSpan;

    public static TracerInterface $tracer;

    /**
     * Start OpenTelemetry for Laravel
     */
    public static function start(Application $app): void
    {
        if (! config('otel.enabled')) {
            return;
        }

        if (! $app->bound(TracerProviderInterface::class)) {
            return;
        }

        if (! $app->bound(UserResolver::class)) {
            $app->bind(UserResolver::class, config('otel.resolvers.user', Resolvers\DefaultUserResolver::class));
        }

        static::$tracer = $app->make(TracerProviderInterface::class)
            ->getTracer('plunkettscott/laravel-otel');

        static::registerWatchers($app);
    }

    public static function captureUnhandledException(Throwable $exception): void
    {
        if (! config('otel.enabled')) {
            return;
        }

        if (! CurrentSpan::get()->isRecording()) {
            return;
        }

        CurrentSpan::get()
            ->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage())
            ->recordException($exception, [
                'exception.line' => $exception->getLine(),
                'exception.file' => $exception->getFile(),
                'exception.code' => $exception->getCode(),
            ]);
    }

    /**
     * Returns a tracer for the application.
     */
    public static function tracer(): TracerInterface
    {
        if (isset(static::$tracer)) {
            return static::$tracer;
        }

        return NoopTracer::getInstance();
    }

    /**
     * Creates a new span wrapping the given callable. If a callable is given, the span is ended and the
     * callable's return value is returned. If an exception is thrown, the span is ended and the exception
     * is recorded and rethrown.
     *
     * If no callable is given, a Span is created and activated, returning an array containing the span and current
     * scope. The scope must be detached and the span must be ended manually. Passing the returned array to spanEnd will
     * end the span and detach the scope for you.
     *
     * @param  string  $name The name of the span
     * @param  callable|null  $callable A callable that will be executed within the span context. The activated Span will be passed as the first argument.
     * @param  int  $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param  iterable  $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     * @return mixed The result of the callable
     *
     * @throws Throwable If the callable throws an exception, it will be rethrown and the span will be ended with the exception recorded.
     */
    public static function span(string $name, callable $callable = null, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): mixed
    {
        if (! config('otel.enabled')) {
            if (is_null($callable)) {
                return null;
            }

            return $callable(CurrentSpan::get());
        }

        $span = Otel::tracer()->spanBuilder($name)
            ->setSpanKind($kind)
            ->setAttributes($attributes)
            ->startSpan();
        $spanScope = $span->activate();

        try {
            return $callable($span);
        } catch (Throwable $e) {
            $span->recordException($e, [
                'exception.line' => $e->getLine(),
                'exception.file' => $e->getFile(),
                'exception.code' => $e->getCode(),
            ]);
            throw $e;
        } finally {
            $spanScope->detach();
            $span->end();
        }
    }
}
