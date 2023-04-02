<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Str;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use Psr\Log\LogLevel;
use Throwable;

class LogWatcher extends Watcher
{
    public ?string $optionsClass = LogWatcherOptions::class;

    /**
     * The available log level priorities.
     */
    private const PRIORITIES = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /**
     * {@inheritDoc}
     */
    public function register(Application $app): void
    {
        $app['events']->listen(MessageLogged::class, [$this, 'recordLog']);
    }

    /**
     * Record a log.
     */
    public function recordLog(MessageLogged $log): void
    {
        if ($this->shouldIgnore($log)) {
            return;
        }

        $attributes = [
            'level' => $log->level,
        ];

        if ($this->option('record_context', true)) {
            $attributes['context'] = json_encode(array_filter($log->context));
        }

        $maxMessageLength = $this->option('max_message_length', -1);
        $message = $maxMessageLength > -1
            ? Str::limit($log->message, $maxMessageLength)
            : $log->message;

        CurrentSpan::get()
            ->addEvent($message, $attributes);
    }

    private function shouldIgnore(MessageLogged $event): bool
    {
        if (self::PRIORITIES[$event->level] < self::PRIORITIES[$this->option('min_level', LogLevel::ERROR)]) {
            // Don't log events below the minimum level defined in the options.
            return true;
        }

        if (isset($event->context['exception']) && $event->context['exception'] instanceof Throwable) {
            // Don't log exception logs as events, they are already logged as events
            // by the ExceptionWatcher.
            return true;
        }

        return false;
    }
}
