<?php

use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use PlunkettScott\LaravelOpenTelemetry\Otel;

if (! function_exists('tracer')) {
    /**
     * Returns an OpenTelemetry tracer instance. If OpenTelemetry is not enabled,
     * a NoopTracer will be returned instead making this function safe to call
     * in all environments.
     *
     * @return TracerInterface
     */
    function tracer(): TracerInterface
    {
        return Otel::tracer();
    }
}

if (! function_exists('span')) {
    /**
     * Creates a new span and activates it in the current context. The span will
     * be ended when the callable returns or throws an exception. The callable may
     * modify the active span by accepting a SpanInterface as its first argument.
     *
     * @param string $name The name of the span
     * @param callable $callable A callable that will be executed within the span context. The activated Span will be passed as the first argument.
     * @param int $kind The kind of span to create. Defaults to SpanKind::KIND_INTERNAL
     * @param iterable $attributes Attributes to add to the span. Defaults to an empty array, but can be any iterable.
     * @return mixed The result of the callable
     *
     * @throws Throwable If the callable throws an exception, it will be rethrown and the span will be ended with the exception recorded.
     */
    function span(string $name, callable $callable, int $kind = SpanKind::KIND_INTERNAL, iterable $attributes = []): mixed
    {
        return Otel::span($name, $callable, $kind, $attributes);
    }
}

if (! function_exists('span_event')) {
    /**
     * @param string $name Event name
     * @param iterable $attributes Event attributes
     * @return SpanInterface
     */
    function span_event(string $name, iterable $attributes = []): SpanInterface
    {
        return CurrentSpan::get()->addEvent($name, $attributes);
    }
}

if (! function_exists('span_attribute')) {
    /**
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return SpanInterface
     */
    function span_attribute(string $name, mixed $value): SpanInterface
    {
        return CurrentSpan::get()->setAttribute($name, $value);
    }
}

if (! function_exists('span_error')) {
    /**
     * Set the Span status to Error with an optional description.
     *
     * @param string|null $description
     * @return SpanInterface
     */
    function span_error(string $description = null): SpanInterface
    {
        return CurrentSpan::get()->setStatus(StatusCode::STATUS_ERROR, $description);
    }
}

if (! function_exists('span_error_if')) {
    /**
     * Set the Span status to Error if the condition is true. Otherwise, return
     * the current span.
     *
     * @param bool $condition
     * @param string|null $description
     * @return SpanInterface
     */
    function span_error_if(bool $condition, string $description = null): SpanInterface
    {
        return $condition ? span_error($description) : CurrentSpan::get();
    }
}

if (! function_exists('span_error_unless')) {
    /**
     * Set the Span status to Error if the condition is false. Otherwise, return
     * the current span.
     *
     * @param bool $condition
     * @param string|null $description
     * @return SpanInterface
     */
    function span_error_unless(bool $condition, string $description = null): SpanInterface
    {
        return $condition ? CurrentSpan::get() : span_error($description);
    }
}
