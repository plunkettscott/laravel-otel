<?php

namespace PlunkettScott\LaravelOpenTelemetry\Concerns;

use Illuminate\Foundation\Application;

trait RegistersWatchers
{
    /**
     * The registered watchers.
     */
    protected static array $watchers = [];

    public static function hasWatcher(string $watcher): bool
    {
        return in_array($watcher, static::$watchers);
    }

    protected static function registerWatchers(Application $app): void
    {
        if (! config('otel.enabled')) {
            return;
        }

        foreach (config('otel.watchers') as $class => $config) {
            if (is_string($class) && $config === false) {
                continue;
            }

            if (is_array($config) && ! ($config['enabled'] ?? true)) {
                continue;
            }

            $watcher = $app->make(is_string($class) ? $class : $config, [
                'options' => is_array($config)
                    ? $config['options'] ?? []
                    : [],
            ]);

            static::$watchers[] = $class;

            $watcher->register($app);
        }
    }
}
