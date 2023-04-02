<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

readonly class EventWatcherOptions extends WatcherOptions
{
    public function __construct(
        public array $ignored = [],
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            ignored: $options['ignored'] ?? [],
        );
    }
}
