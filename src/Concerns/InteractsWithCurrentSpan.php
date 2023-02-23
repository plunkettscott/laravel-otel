<?php

namespace PlunkettScott\LaravelOpenTelemetry\Concerns;

use Exception;
use OpenTelemetry\SDK\Trace\Span;

trait InteractsWithCurrentSpan
{
    public static function traceId(): string
    {
        return Span::getCurrent()->getContext()->getTraceId();
    }

    public static function spanId(): string
    {
        return Span::getCurrent()->getContext()->getSpanId();
    }

    public static function traceState(): string
    {
        return Span::getCurrent()->getContext()->getTraceState();
    }

    public static function recordException(Exception $e): void
    {
        Span::getCurrent()->recordException($e);
    }

    public static function setAttribute(string $key, $value): void
    {
        Span::getCurrent()->setAttribute($key, $value);
    }

    public static function setAttributes(array $attributes): void
    {
        Span::getCurrent()->setAttributes($attributes);
    }

    public static function addEvent(string $name, array $attributes = []): void
    {
        Span::getCurrent()->addEvent($name, $attributes);
    }
}
