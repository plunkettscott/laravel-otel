<?php

use PlunkettScott\LaravelOpenTelemetry\Watchers\CacheWatcher;
use PlunkettScott\LaravelOpenTelemetry\Watchers\CacheWatcherOptions;

beforeEach(function () {
    $this->enableWatcher(CacheWatcher::class, (new CacheWatcherOptions(
        record_cache_hit: true,
        record_cache_miss: true,
        record_cache_set: true,
        record_cache_forget: true,
    ))->toArray());
});

it('records cache hits', function () {
    $fake = $this->withFakeSpan();

    cache()->remember('hit', 60, fn () => 'test');
    cache()->get('hit');

    $fake->assertEventExists('cache hit', [
        'key' => 'hit',
    ]);
});

it('records cache misses', function () {
    $fake = $this->withFakeSpan();

    cache()->get('miss');

    $fake->assertEventExists('cache miss', [
        'key' => 'miss',
    ]);
});

it('records cache put without a ttl', function () {
    $fake = $this->withFakeSpan();

    cache()->put('put', 'test');

    $fake->assertEventExists('cache set', [
        'key' => 'put',
        'expires_at' => 'never',
        'expires_in_seconds' => 'never',
        'expires_in_human' => 'never',
    ]);
});

it('records cache put with a ttl', function () {
    $fake = $this->withFakeSpan();

    $expiredAt = now()->addSeconds(60);
    cache()->put('put', 'test', $expiredAt);

    $fake->assertEventExists('cache set', [
        'key' => 'put',
        'expires_at' => $expiredAt->getTimestamp(),
        'expires_in_seconds' => 60,
    ]);
});

it('records cache forget', function () {
    $fake = $this->withFakeSpan();

    cache()->put('forget', 'test');
    cache()->forget('forget');

    $fake->assertEventExists('cache forget', [
        'key' => 'forget',
    ]);
});
