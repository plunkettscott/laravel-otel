<?php

namespace PlunkettScott\LaravelOpenTelemetry\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;

trait AttachesTraceToLogContext
{
    public function traceLogContext(array &$merged = []): array
    {
        if (! $this->shouldAttachTraceToLogContext()) {
            return $merged;
        }

        $traceIdField = config('otel.log_context_fields.trace_id', 'trace_id');

        if (is_string($traceIdField)) {
            Arr::set($merged, $traceIdField, CurrentSpan::get()->getContext()->getTraceId());
        }

        return $merged;
    }

    public function attachTraceToLogContext(array $extra = []): void
    {
        if (! $this->shouldAttachTraceToLogContext()) {
            return;
        }

        Log::withContext($this->traceLogContext($extra));
    }

    private function shouldAttachTraceToLogContext(): bool
    {
        return config('otel.log_context_fields.enabled', true);
    }
}
