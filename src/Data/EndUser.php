<?php

namespace PlunkettScott\LaravelOpenTelemetry\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\SemConv\TraceAttributes;

class EndUser
{
    protected Collection $attributes;

    public function __construct()
    {
        $this->attributes = new Collection();
    }

    public function setId(string $id): void
    {
        $this->attributes = $this->attributes->put(TraceAttributes::ENDUSER_ID, $id);
    }

    public function getId(): mixed
    {
        return $this->attributes->get(TraceAttributes::ENDUSER_ID);
    }

    public function setScopes(array $scopes): void
    {
        $this->attributes = $this->attributes->put(TraceAttributes::ENDUSER_SCOPE, json_encode($scopes));
    }

    public function getScopes(): string
    {
        return $this->attributes->get(TraceAttributes::ENDUSER_SCOPE);
    }

    public function setRole(string $role): void
    {
        $this->attributes = $this->attributes->put(TraceAttributes::ENDUSER_ROLE, $role);
    }

    public function getRole(): string
    {
        return $this->attributes->get(TraceAttributes::ENDUSER_ROLE);
    }

    public function setAttribute(string $key, string|array $value): void
    {
        if (! str_starts_with($key, 'enduser.')) {
            $key = 'enduser.' . $key;
        }

        if ($key === TraceAttributes::ENDUSER_ID) {
            $this->setId($value);
            return;
        }

        if ($key === TraceAttributes::ENDUSER_SCOPE) {
            $this->setScopes($value);
            return;
        }

        if ($key === TraceAttributes::ENDUSER_ROLE) {
            $this->setRole($value);
            return;
        }

        $this->attributes = $this->attributes->put($key, $value);
    }

    public function setAttributes(array|Arrayable $attributes): void
    {
        if ($attributes instanceof Arrayable) {
            $attributes = $attributes->toArray();
        }

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function toArray(): array
    {
        return $this->attributes->toArray();
    }

    public function addToSpan(SpanInterface $span): void
    {
        foreach ($this->attributes as $key => $value) {
            $span->setAttribute($key, $value);
        }
    }

}
