<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

final readonly class RequestWatcherOptions extends WatcherOptions
{
    /**
     * @param bool $continue_trace Whether to continue the trace if a trace header is present in the request. If false, a new trace will be started for each request, even if the request is part of a trace.
     * @param bool $record_route Whether to record the route in the span attributes.
     * @param bool $record_user Whether to record the authenticated User in the span attributes. If true, the user's ID will be recorded in the span attributes, and any attributes specified in $record_user_attributes will also be recorded.
     */
    public function __construct(
        public bool $continue_trace = true,
        public bool $record_route = true,
        public bool $record_user = true,
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            $options['continue_trace'] ?? true,
            $options['record_route'] ?? true,
            $options['record_user'] ?? true,
        );
    }
}
