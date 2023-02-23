<?php

namespace PlunkettScott\LaravelOpenTelemetry\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'otel:install';

    protected $description = 'Install all of the OpenTelemetry for Laravel resources';

    public function handle(): void
    {
        $this->comment('Publishing OpenTelemetry for Laravel Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'otel-provider']);

        $this->comment('Publishing OpenTelemetry for Laravel Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'otel-config']);

        $this->comment('Publishing OpenTelemetry for Laravel Middleware...');
        $this->callSilent('vendor:publish', ['--tag' => 'otel-middleware']);

        $this->registerOtelServiceProvider();

        $this->info('OpenTelemetry for Laravel scaffolding installed successfully.');
    }

    protected function registerOtelServiceProvider(): void
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\OtelServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\OtelServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/OtelServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/OtelServiceProvider.php'))
        ));
    }
}
