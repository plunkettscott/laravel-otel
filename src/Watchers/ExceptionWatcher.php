<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\Events\MessageLogged;
use OpenTelemetry\API\Trace\StatusCode;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use Throwable;

class ExceptionWatcher extends Watcher
{
    public ?string $optionsClass = ExceptionWatcherOptions::class;

    /**
     * {@inheritDoc}
     */
    public function register(Application $app): void
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordException']);
    }

    /**
     * Record an exception.
     */
    public function recordException(MessageLogged $log): void
    {
        if ($this->shouldIgnore($log)) {
            return;
        }

        $exception = $log->context['exception'];

        $attributes = [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
        ];

        CurrentSpan::get()
            ->addEvent('Exception', $attributes)
            ->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
    }

    private function shouldIgnore(MessageLogged $event): bool
    {
        if (! isset($event->context['exception']) ||
            ! $event->context['exception'] instanceof Throwable) {
            return true;
        }

        if ($this->option('ignored', [])) {
            foreach ($this->option('ignored', []) as $ignored) {
                if ($event->context['exception'] instanceof $ignored) {
                    return true;
                }
            }
        }

        return false;
    }
}
