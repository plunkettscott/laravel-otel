<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

readonly class RedisWatcherOptions extends WatcherOptions
{
    /**
     * @param  bool  $record_command Whether to record the Redis command as a span attribute.
     * @param  array  $ignore_commands An array of Redis commands to ignore. If a command is executed that is in this array, a span will not be created.
     */
    public function __construct(
        public bool $record_command = true,
        public array $ignore_commands = [],
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            $options['record_command'] ?? true,
            $options['ignore_commands'] ?? [],
        );
    }
}
