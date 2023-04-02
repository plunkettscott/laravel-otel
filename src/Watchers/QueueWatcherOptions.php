<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

readonly class QueueWatcherOptions extends WatcherOptions
{
    /**
     * @param bool $trace_by_default When true, all jobs will be attached to the trace and recorded as spans by default. When false, only jobs that are explicitly marked as trace-aware will be attached to the trace and recorded as spans. This option can be overridden on a per-job basis by implementing the TraceAware or NotTraceAware interface.
     * @param array $ignored An array of job classes to ignore. These jobs will not be attached to the trace and will not be recorded as spans.
     */
    public function __construct(
        public bool  $trace_by_default = true,
        public array $ignored = [],
    ) {
    }

    public static function fromArray(array $options): WatcherOptions
    {
        return new self(
            trace_by_default: $options['trace_by_default'] ?? true,
            ignored: $options['ignored'] ?? [],
        );
    }
}
