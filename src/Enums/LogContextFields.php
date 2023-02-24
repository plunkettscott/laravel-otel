<?php

namespace PlunkettScott\LaravelOpenTelemetry\Enums;

enum LogContextFields: string
{
    /**
     * The current trace ID as a log context field. This is useful when you want to
     * correlate log entries with traces for debugging purposes.
     */
    const TRACE_ID = 'trace_id';
}
