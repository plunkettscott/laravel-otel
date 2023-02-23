<?php

namespace PlunkettScott\LaravelOpenTelemetry\Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Orchestra\Testbench\TestCase;
use PlunkettScott\LaravelOpenTelemetry\OtelApplicationServiceProvider;
use PlunkettScott\LaravelOpenTelemetry\OtelServiceProvider;

class FeatureTestCase extends TestCase
{
    use LazilyRefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            OtelServiceProvider::class,
            OtelApplicationServiceProvider::class,
        ];
    }

    protected function resolveApplicationCore($app): void
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'self-testing';
        });
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('otel.enabled', true);
        $config->set('logging.default', 'errorlog');
        $config->set('database.default', 'testbench');
        $config->set('telescope.storage.database.connection', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
