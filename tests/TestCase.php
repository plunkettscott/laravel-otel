<?php

namespace PlunkettScott\LaravelOpenTelemetry\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;
use PlunkettScott\LaravelOpenTelemetry\Otel;
use PlunkettScott\LaravelOpenTelemetry\OtelApplicationServiceProvider;
use PlunkettScott\LaravelOpenTelemetry\OtelServiceProvider;
use PlunkettScott\LaravelOpenTelemetry\Tests\Support\FakeSpan;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            OtelServiceProvider::class,
            OtelApplicationServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $config = $app['config'];
        $config->set('otel.enabled', true);
        $config->set('logging.default', 'syslog');
        $config->set('database.default', 'testbench');
        $config->set('telescope.storage.database.connection', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }


    /**
     * Set a config key to a value.
     *
     * @param array<string, mixed> $merge
     * @return void
     */
    public function withConfig(array $merge): void
    {
        foreach ($merge as $key => $value) {
            $this->app['config']->set($key, $value);
        }
    }

    public function startOtel(): void
    {
        Otel::start($this->app);
    }

    public function enableWatcher(string $watcher, array $options = []): void
    {
        $this->withConfig([
            'otel.watchers' => [
                $watcher => [
                    'enabled' => true,
                    'options' => $options,
                ],
            ],
        ]);

        $this->startOtel();
    }

    /**
     * Creates and returns a FakeSpan after setting it as the current span.
     *
     * @param FakeSpan|null $span
     * @return FakeSpan
     */
    public function withFakeSpan(FakeSpan $span = null): FakeSpan
    {
        $span = $span ?? new FakeSpan();
        CurrentSpan::$currentSpan = $span;

        return $span;
    }

}
