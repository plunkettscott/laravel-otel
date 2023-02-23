<?php

namespace PlunkettScott\LaravelOpenTelemetry;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanInterface;

class CurrentSpan
{
    public static SpanInterface $currentSpan;

    public static function get(): SpanInterface
    {
        if (isset(self::$currentSpan)) {
            return self::$currentSpan;
        }

        return Span::getCurrent();
    }
}
