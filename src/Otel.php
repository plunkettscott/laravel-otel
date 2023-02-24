<?php

namespace PlunkettScott\LaravelOpenTelemetry;

use Illuminate\Foundation\Application;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use Throwable;

class Otel
{
    use Concerns\RegistersWatchers,
        Concerns\InteractsWithCurrentSpan;

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

        $attributes = [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
        ];

        CurrentSpan::get()
            ->addEvent('Unhandled Exception', $attributes)
            ->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
    }
}
