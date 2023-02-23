<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use InvalidArgumentException;

abstract readonly class WatcherOptions
{
    abstract public static function fromArray(array $options): self;

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (! property_exists($this, $key)) {
            throw new InvalidArgumentException("The option [$key] does not exist for [".static::class.'].');
        }

        return $this->{$key} ?? $default;
    }
}
