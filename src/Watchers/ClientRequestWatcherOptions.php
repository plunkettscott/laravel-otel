<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

final readonly class ClientRequestWatcherOptions extends WatcherOptions
{
    /**
     * @param  bool  $record_errors When true, non-successful HTTP responses will be recorded as errors.
     * @param  int[]  $record_errors_except_statuses An array of HTTP status codes to ignore when recording errors. For example, if you want to ignore 404 errors, you would pass [404].
     */
    public function __construct(
        public bool $record_errors = true,
        public array $record_errors_except_statuses = [],
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            $options['record_errors'] ?? true,
            $options['record_errors_except_statuses'] ?? [],
        );
    }
}
