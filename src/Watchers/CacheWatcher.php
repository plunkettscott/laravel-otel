<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;

class CacheWatcher extends Watcher
{
    public ?string $optionsClass = CacheWatcherOptions::class;

    /**
     * @inheritDoc
     */
    public function register(Application $app): void
    {
        $app['events']->listen(CacheHit::class, [$this, 'recordCacheHit']);
        $app['events']->listen(CacheMissed::class, [$this, 'recordCacheMiss']);

        $app['events']->listen(KeyWritten::class, [$this, 'recordCacheSet']);
        $app['events']->listen(KeyForgotten::class, [$this, 'recordCacheForget']);
    }

    public function recordCacheHit(CacheHit $event): void
    {
        if ((! $this->option('record_cache_hit', true)) || $this->shouldIgnore($event)) {
            return;
        }

        $this->addEvent('cache hit', [
            'key' => $event->key,
            'tags' => json_encode($event->tags),
        ]);
    }

    public function recordCacheMiss(CacheMissed $event): void
    {
        if ((! $this->option('record_cache_miss', true)) || $this->shouldIgnore($event)) {
            return;
        }

        $this->addEvent('cache miss', [
            'key' => $event->key,
            'tags' => json_encode($event->tags),
        ]);
    }

    public function recordCacheSet(KeyWritten $event): void
    {
        if ((! $this->option('record_cache_set', true)) || $this->shouldIgnore($event)) {
            return;
        }

        $ttl = property_exists($event, 'minutes')
            ? $event->minutes * 60
            : $event->seconds;

        $this->addEvent('cache set', [
            'key' => $event->key,
            'tags' => json_encode($event->tags),
            'expires_at' => $ttl > 0
                ? now()->addSeconds($ttl)->getTimestamp()
                : 'never',
            'expires_in_seconds' => $ttl > 0
                ? $ttl
                : 'never',
            'expires_in_human' => $ttl > 0
                ? now()->addSeconds($ttl)->diffForHumans()
                : 'never',
        ]);
    }

    public function recordCacheForget(KeyForgotten $event): void
    {
        if ((! $this->option('record_cache_forget', true)) || $this->shouldIgnore($event)) {
            return;
        }

        $this->addEvent('cache forget', [
            'key' => $event->key,
            'tags' => json_encode($event->tags),
        ]);
    }

    private function addEvent(string $name, iterable $attributes = []): void
    {
        CurrentSpan::get()
            ->addEvent($name, $attributes);
    }

    private function shouldIgnore($event): bool
    {
        return Str::is(array_merge_recursive([
            'illuminate:queue:restart',
            'framework/schedule*',
            'telescope:*',
        ], $this->option('ignored', [])), $event->key);
    }
}
