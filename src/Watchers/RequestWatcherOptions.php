<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

readonly class RequestWatcherOptions extends WatcherOptions
{
    /**
     * @param  bool  $continue_trace Whether to continue the trace if a trace header is present in the request. If false, a new trace will be started for each request, even if the request is part of a trace.
     * @param  array  $middleware_groups An array of middleware groups to automatically trace. If a request is made that is part of a middleware group in this array, a span will be created.
     */
    public function __construct(
        public bool $continue_trace = true,
        public array $middleware_groups = [
            'web',
            'api',
        ],
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            $options['continue_trace'] ?? true,
            $options['middleware_groups'] ?? [
                'web',
                'api',
            ],
        );
    }
}
