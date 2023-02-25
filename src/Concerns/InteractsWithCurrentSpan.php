<?php

namespace PlunkettScott\LaravelOpenTelemetry\Concerns;

use Exception;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;

trait InteractsWithCurrentSpan
{
    public static function traceId(): string
    {
        return CurrentSpan::get()->getContext()->getTraceId();
    }

    public static function spanId(): string
    {
        return CurrentSpan::get()->getContext()->getSpanId();
    }

    public static function recordException(Exception $e): void
    {
        CurrentSpan::get()->recordException($e);
    }

    public static function setAttribute(string $key, $value): void
    {
        CurrentSpan::get()->setAttribute($key, $value);
    }

    public static function setAttributes(iterable $attributes): void
    {
        CurrentSpan::get()->setAttributes($attributes);
    }

    public static function addEvent(string $name, iterable $attributes = [], int|null $timestamp = null): void
    {
        CurrentSpan::get()->addEvent($name, $attributes, $timestamp);
    }
}
