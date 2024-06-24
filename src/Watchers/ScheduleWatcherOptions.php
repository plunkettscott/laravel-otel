<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

final readonly class ScheduleWatcherOptions extends WatcherOptions
{
    /**
     * @param bool $record_output When true, the output of the command will be recorded as an event on the span. This can be useful for debugging, but can be a risk if the output contains sensitive information.
     */
    public function __construct(
        public bool $record_output = false,
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            record_output: $options['record_output'] ?? false,
        );
    }
}
