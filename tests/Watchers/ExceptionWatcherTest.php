<?php

namespace PlunkettScott\LaravelOpenTelemetry\Tests\Watchers;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Mockery;
use OpenTelemetry\API\Trace\SpanInterface;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use PlunkettScott\LaravelOpenTelemetry\Tests\FeatureTestCase;
use PlunkettScott\LaravelOpenTelemetry\Watchers\ExceptionWatcher;

class ExceptionWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('logging.default', 'syslog');
        $app->get('config')->set('otel.watchers', [
            ExceptionWatcher::class => [
                'enabled' => true,
                'options' => [
                    'ignored' => [
                        TestIgnoredException::class,
                    ],
                ],
            ],
        ]);
    }

    public function test_exception_watcher_adds_span_event()
    {
        $exception = new TestException('Test Exception');

        CurrentSpan::$currentSpan = Mockery::mock(SpanInterface::class)
            ->shouldReceive('addEvent')
            ->withArgs(['Exception', [
                'class' => TestException::class,
                'file' => __FILE__,
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
            ]])
            ->once()
            ->andReturn(Mockery::self())
            ->shouldReceive('setStatus')
            ->withArgs([
                'Error',
                $exception->getMessage(),
            ])
            ->once()
            ->andReturn(Mockery::self())
            ->getMock();

        $handler = $this->app->get(ExceptionHandler::class);
        $handler->report($exception);
    }

    public function test_exception_watcher_ignores_ignored_exceptions()
    {
        $exception = new TestIgnoredException('Test Exception');

        CurrentSpan::$currentSpan = Mockery::mock(SpanInterface::class)
            ->shouldNotReceive('addEvent', 'setStatus')
            ->getMock();

        $handler = $this->app->get(ExceptionHandler::class);
        $handler->report($exception);
    }
}

class TestException extends Exception
{
}

class TestIgnoredException extends Exception
{
}
