<?php

namespace PlunkettScott\LaravelOpenTelemetry\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Testing\Fluent\AssertableJson;
use InvalidArgumentException;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\Context\ContextKeyInterface;
use OpenTelemetry\Context\ScopeInterface;
use PHPUnit\Framework\AssertionFailedError;
use Throwable;
use PHPUnit\Framework\Assert as PHPUnit;

class FakeSpan implements SpanInterface
{
    private ?FakeScope $scope = null;
    private Collection $attributes;
    private Collection $events;
    private ?Throwable $exception;
    private array $exceptionAttributes = [];
    private ?string $name = 'Test Span';
    private ?string $status = StatusCode::STATUS_UNSET;
    private ?string $statusDescription = null;
    private bool $ended = false;

    public function __construct()
    {
        $this->attributes = new Collection();
        $this->events = new Collection();
    }

    /**
     * @inheritDoc
     */
    public function activate(): ScopeInterface
    {
        if (!isset($this->scope)) {
            $this->scope = new FakeScope();
        }

        return $this->scope;
    }

    /**
     * @inheritDoc
     */
    public function storeInContext(ContextInterface $context): ContextInterface
    {
        return $context;
    }

    /**
     * @inheritDoc
     */
    public static function fromContext(ContextInterface $context): SpanInterface
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public static function getCurrent(): SpanInterface
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public static function getInvalid(): SpanInterface
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public static function wrap(SpanContextInterface $spanContext): SpanInterface
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getContext(): SpanContextInterface
    {
        return SpanContext::getInvalid();
    }

    /**
     * @inheritDoc
     */
    public function isRecording(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $key, $value): SpanInterface
    {
        if (!isset($this->attributes)) {
            $this->attributes = new Collection();
        }

        $this->attributes->put($key, $value);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setAttributes(iterable $attributes): SpanInterface
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addEvent(string $name, iterable $attributes = [], int $timestamp = null): SpanInterface
    {
        if (!isset($this->events)) {
            $this->events = new Collection();
        }

        $this->events->push([
            'name' => $name,
            'attributes' => $attributes,
            'timestamp' => $timestamp,
        ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function recordException(Throwable $exception, iterable $attributes = []): SpanInterface
    {
        $this->exception = $exception;
        $this->exceptionAttributes = $attributes;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function updateName(string $name): SpanInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $code, string $description = null): SpanInterface
    {
        if (!in_array($code, [
            StatusCode::STATUS_OK,
            StatusCode::STATUS_ERROR,
            StatusCode::STATUS_UNSET,
        ])) {
            throw new InvalidArgumentException('Invalid status code');
        }

        $this->status = $code;
        $this->statusDescription = $description;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function end(int $endEpochNanos = null): void
    {
        $this->ended = true;
    }

    public function getAttributes(): Collection
    {
        return $this->attributes;
    }

    public function assertAttributeExists(string $key): void
    {
        PHPUnit::assertArrayHasKey($key, $this->attributes->toArray(),
            "Attribute '$key' does not exist.");
    }

    public function assertAttributeEquals(string $key, $value): void
    {
        PHPUnit::assertEquals($value, $this->attributes->get($key),
            "Attribute '$key' does not equal '$value'.");
    }

    public function assertAttributeNotEquals(string $key, $value): void
    {
        PHPUnit::assertNotEquals($value, $this->attributes->get($key),
            "Attribute '$key' equals '$value'.");
    }

    public function assertAttributeContains(string $key, $value): void
    {
        PHPUnit::assertContains($value, $this->attributes->get($key),
            "Attribute '$key' does not contain '$value'.");
    }

    public function assertAttributeNotContains(string $key, $value): void
    {
        PHPUnit::assertNotContains($value, $this->attributes->get($key),
            "Attribute '$key' contains '$value'.");
    }

    public function assertAttributeMatches(string $key, string $pattern): void
    {
        PHPUnit::assertMatchesRegularExpression($pattern, $this->attributes->get($key),
            "Attribute '$key' does not match pattern '$pattern'.");
    }

    public function assertAttributeNotMatches(string $key, string $pattern): void
    {
        PHPUnit::assertDoesNotMatchRegularExpression($pattern, $this->attributes->get($key),
            "Attribute '$key' matches pattern '$pattern'.");
    }

    public function assertAttributeAsJson(string $key): AssertableJson
    {
        return AssertableJson::fromArray($this->attributes->get($key));
    }

    public function assertStatus(string $status, string $description = null): void
    {
        PHPUnit::assertEquals($status, $this->status, "Status does not equal '$status'.");

        if ($description !== null) {
            $this->assertStatusDescription($description);
        }
    }

    public function assertStatusDescription(string $description): void
    {
        PHPUnit::assertEquals($description, $this->statusDescription, "Status description does not equal '$description'.");
    }

    public function assertName(string $name): void
    {
        PHPUnit::assertEquals($name, $this->name, "Span name does not equal '$name'.");
    }

    public function assertEnded(): void
    {
        PHPUnit::assertTrue($this->ended, 'Span has not ended');
    }

    public function assertNotEnded(): void
    {
        PHPUnit::assertFalse($this->ended, 'Span has ended');
    }

    public function assertException(Throwable $exception): void
    {
        PHPUnit::assertSame($exception, $this->exception, 'Exception does not match');
    }

    public function assertExceptionAttributeExists(string $key): void
    {
        PHPUnit::assertArrayHasKey($key, $this->exceptionAttributes,
            "Exception attribute '$key' does not exist.");
    }

    public function assertExceptionAttributeEquals(string $key, $value): void
    {
        PHPUnit::assertEquals($value, $this->exceptionAttributes[$key],
            "Exception attribute '$key' does not equal '$value'.");
    }

    public function assertExceptionAttributeNotEquals(string $key, $value): void
    {
        PHPUnit::assertNotEquals($value, $this->exceptionAttributes[$key],
            "Exception attribute '$key' equals '$value'.");
    }

    public function assertEventCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->events->toArray(), "Event count does not equal '$count'.");
    }

    public function assertEventExists(string $name, array $attributes = []): void
    {
        $result = $this->events->filter(function ($event) use ($name) {
            return $event['name'] === $name;
        });

        PHPUnit::assertNotCount(0, $result->toArray(), "Event '$name' does not exist.");

        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $key => $value) {
            $result = $result->filter(function ($event) use ($key, $value) {
                return $event['attributes'][$key] === $value;
            });
        }

        PHPUnit::assertNotCount(0, $result->toArray(), "Event '$name' does not exist with attributes.");
    }

    public function assertEventMissing(string $name): void
    {
        $result = $this->events->filter(function ($event) use ($name) {
            return $event['name'] === $name;
        });

        PHPUnit::assertCount(0, $result->toArray(), "Event '$name' exists.");
    }

    public function assertEventAttributeExists(string $name, string $key): void
    {
        $result = $this->events->filter(function ($event) use ($name, $key) {
            return $event['name'] === $name && isset($event['attributes'][$key]);
        });

        PHPUnit::assertNotCount(0, $result->toArray(), "Event attribute '$key' does not exist.");
    }

    public function assertEventAttributeMissing(string $name, string $key): void
    {
        $result = $this->events->filter(function ($event) use ($name, $key) {
            return $event['name'] === $name && isset($event['attributes'][$key]);
        });

        PHPUnit::assertCount(0, $result->toArray(), "Event attribute '$key' exists.");
    }

    public function assertEventAttributeEquals(string $name, string $key, $value): void
    {
        $result = $this->events->filter(function ($event) use ($name, $key, $value) {
            return $event['name'] === $name && $event['attributes'][$key] === $value;
        });

        PHPUnit::assertNotCount(0, $result->toArray(), "Event attribute '$key' does not equal '$value'.");
    }

    public function dumpEvents(): void
    {
        dump($this->events->toArray());
    }
}
