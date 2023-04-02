<?php

use OpenTelemetry\API\Trace\SpanInterface;
use Illuminate\Contracts\Debug\ExceptionHandler;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use PlunkettScott\LaravelOpenTelemetry\Otel;
use PlunkettScott\LaravelOpenTelemetry\Tests\Support\Exceptions\TestException;
use PlunkettScott\LaravelOpenTelemetry\Tests\Support\Exceptions\TestIgnoredException;
use PlunkettScott\LaravelOpenTelemetry\Tests\Support\FakeSpan;
use PlunkettScott\LaravelOpenTelemetry\Tests\TestCase;
use PlunkettScott\LaravelOpenTelemetry\Watchers\ExceptionWatcher;

beforeEach(function () {
    $this->enableWatcher(ExceptionWatcher::class, [
        'ignored' => [
            TestIgnoredException::class,
        ],
    ]);
});

it('adds span events', function () {
    $fake = $this->withFakeSpan();

    $exception = new TestException('Test Exception');
    $handler = app(ExceptionHandler::class);
    $handler->report($exception);

    $fake->assertStatus('Error', $exception->getMessage());
    $fake->assertEventExists('Exception', [
        'class' => TestException::class,
        'file' => __FILE__,
        'line' => $exception->getLine(),
        'message' => $exception->getMessage(),
    ]);
});

it('ignores ignored exceptions', function () {
    $fake = $this->withFakeSpan();

    $exception = new TestIgnoredException('Test Ignored Exception');
    $handler = $this->app->get(ExceptionHandler::class);
    $handler->report($exception);

    $fake->assertEventMissing('Exception');
});
