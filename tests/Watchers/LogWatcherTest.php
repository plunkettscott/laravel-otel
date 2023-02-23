<?php

namespace PlunkettScott\LaravelOpenTelemetry\Tests\Watchers;

use Mockery;
use OpenTelemetry\API\Trace\SpanInterface;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use PlunkettScott\LaravelOpenTelemetry\Tests\FeatureTestCase;
use PlunkettScott\LaravelOpenTelemetry\Watchers\LogWatcher;

class LogWatcherTest extends FeatureTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->get('config')->set('logging.default', 'syslog');

        $options = match ($this->getName(false)) {
            'test_that_min_level_is_logged' => [
                'min_level' => 'debug',
                'record_context' => true,
            ],
            'test_that_context_is_not_recorded_when_record_context_is_false' => [
                'min_level' => 'debug',
                'record_context' => false,
            ],
            'test_that_max_message_length_is_respected' => [
                'min_level' => 'debug',
                'record_context' => true,
                'max_message_length' => 5,
            ],
            default => [
                'min_level' => 'error',
                'record_context' => true,
            ],
        };

        $app->get('config')->set('otel.watchers', [
            LogWatcher::class => [
                'enabled' => true,
                'options' => $options,
            ],
        ]);
    }

    public function test_that_min_level_is_logged()
    {
        CurrentSpan::$currentSpan = Mockery::mock(SpanInterface::class)
            ->shouldReceive('addEvent')
            ->once()
            ->withArgs(['debug message', [
                'level' => 'debug',
                'context' => '[]',
            ]])
            ->andReturn(Mockery::self())
            ->getMock();

        $this->app['log']->debug('debug message');
    }

    public function test_that_levels_below_min_level_are_not_logged()
    {
        $mockedSpan = Mockery::mock(SpanInterface::class);
        $mockedSpan->shouldReceive('addEvent')
            ->once()
            ->withArgs(['error message', [
                'level' => 'error',
                'context' => '[]',
            ]])
            ->andReturn(Mockery::self())
            ->shouldReceive('addEvent')
            ->once()
            ->withArgs(['critical message', [
                'level' => 'critical',
                'context' => '[]',
            ]])
            ->andReturn(Mockery::self())
            ->shouldReceive('addEvent')
            ->once()
            ->withArgs(['alert message', [
                'level' => 'alert',
                'context' => '[]',
            ]])
            ->andReturn(Mockery::self())
            ->shouldReceive('addEvent')
            ->once()
            ->withArgs(['emergency message', [
                'level' => 'emergency',
                'context' => '[]',
            ]])
            ->andReturn(Mockery::self())
            ->getMock();

        CurrentSpan::$currentSpan = $mockedSpan;

        $this->app['log']->debug('debug message');
        $this->app['log']->info('info message');
        $this->app['log']->notice('notice message');
        $this->app['log']->warning('warning message');
        $this->app['log']->error('error message');
        $this->app['log']->critical('critical message');
        $this->app['log']->alert('alert message');
        $this->app['log']->emergency('emergency message');
    }

    public function test_that_context_is_logged()
    {
        $mockedSpan = Mockery::mock(SpanInterface::class);
        $mockedSpan->shouldReceive('addEvent')
            ->once()
            ->withArgs(['error message', [
                'level' => 'error',
                'context' => '{"foo":"bar"}',
            ]])
            ->andReturn(Mockery::self())
            ->getMock();

        CurrentSpan::$currentSpan = $mockedSpan;

        $this->app['log']->error('error message', ['foo' => 'bar']);
    }

    public function test_that_context_is_not_recorded_when_record_context_is_false()
    {
        $mockedSpan = Mockery::mock(SpanInterface::class);
        $mockedSpan->shouldReceive('addEvent')
            ->once()
            ->withArgs(['error message', [
                'level' => 'error',
            ]])
            ->andReturn(Mockery::self())
            ->getMock();

        CurrentSpan::$currentSpan = $mockedSpan;

        $this->app['log']->error('error message');
    }

    public function test_that_max_message_length_is_respected()
    {
        $mockedSpan = Mockery::mock(SpanInterface::class);
        $mockedSpan->shouldReceive('addEvent')
            ->once()
            ->withArgs(['error...', [
                'level' => 'error',
                'context' => '{"foo":"bar"}',
            ]])
            ->andReturn(Mockery::self())
            ->getMock();

        CurrentSpan::$currentSpan = $mockedSpan;

        $this->app['log']->error('error message', ['foo' => 'bar']);
    }
}
