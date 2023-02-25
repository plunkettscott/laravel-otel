<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Psr\Log\LogLevel;

final readonly class LogWatcherOptions extends WatcherOptions
{
    /**
     * @param  string  $min_level The minimum log level to record. Any log messages below this level will not be recorded. Defaults to error.
     * @param  int  $max_message_length The maximum length of the log message to record. Any log messages longer than this will be truncated. Set to -1 to disable truncation.
     * @param  bool  $record_context  Whether to record the log context as a span attribute. This can be useful for recording additional information about the log message.
     */
    public function __construct(
        public string $min_level = LogLevel::ERROR,
        public int $max_message_length = -1,
        public bool $record_context = true,
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            $options['min_level'] ?? LogLevel::ERROR,
            $options['max_message_length'] ?? -1,
            $options['record_context'] ?? true,
        );
    }
}
