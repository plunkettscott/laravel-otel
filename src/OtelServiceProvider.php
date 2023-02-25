<?php

namespace PlunkettScott\LaravelOpenTelemetry;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use PlunkettScott\LaravelOpenTelemetry\Console\Commands;
use PlunkettScott\LaravelOpenTelemetry\Contracts\NamedSpanManagerContract;
use PlunkettScott\LaravelOpenTelemetry\Support\NamedSpanManager;

class OtelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->registerCommands();
        $this->registerPublishing();

        if (! config('otel.enabled')) {
            return;
        }

        Otel::start($this->app);
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'otel');
    }

    private function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('otel.php'),
        ], 'otel-config');

        $this->publishes([
            __DIR__.'/../stubs/OtelServiceProvider.stub' => app_path('Providers/OtelServiceProvider.php'),
        ], 'otel-provider');
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
            ]);
        }
    }
}
