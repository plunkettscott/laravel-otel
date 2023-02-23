<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;

abstract class Watcher
{
    /**
     * The configured watcher options.
     */
    public array|WatcherOptions $options = [];

    /**
     * The class name of the options class.
     */
    public ?string $optionsClass = null;

    /**
     * The tracer provider.
     */
    public TracerInterface $tracer;

    /**
     * Create a new watcher instance.
     */
    public function __construct(
        TracerProviderInterface $tracerProvider,
        array $options = [],
    ) {
        if (is_null($options) && ! is_null($this->optionsClass)) {
            $options = new $this->optionsClass();
        }

        if (is_array($options) && ! is_null($this->optionsClass) && class_exists($this->optionsClass)) {
            $options = $this->optionsClass::fromArray($options);
        }

        $this->options = $options;
        $this->tracer = $tracerProvider->getTracer($this::class);
    }

    /**
     * Register the watcher.
     */
    abstract public function register(Application $app): void;

    /**
     * Return an option value. If the option is not set, return the default value.
     */
    protected function option(string $key, mixed $default = null): mixed
    {
        if ($this->options instanceof WatcherOptions) {
            return $this->options->get($key, $default);
        }

        if (is_array($this->options)) {
            return $this->options[$key] ?? $default;
        }

        return $default;
    }
}
