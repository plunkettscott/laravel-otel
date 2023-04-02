<?php

use PlunkettScott\LaravelOpenTelemetry\Watchers\LogWatcher;

it('logs the min level', function () {
    $fake = $this->withFakeSpan();
    $this->enableWatcher(LogWatcher::class, [
        'min_level' => 'debug',
        'record_context' => true,
    ]);

    app('log')
        ->debug('debug message');

//    $fake->dumpEvents();
    $fake->assertEventExists('debug message');
});

it('does not log below the min level', function () {
    $fake = $this->withFakeSpan();
    $this->enableWatcher(LogWatcher::class, [
        'min_level' => 'info',
        'record_context' => true,
    ]);

    app('log')
        ->debug('debug message');

    $fake->assertEventMissing('debug message');
});

it('logs the context', function () {
    $fake = $this->withFakeSpan();
    $this->enableWatcher(LogWatcher::class, [
        'min_level' => 'debug',
        'record_context' => true,
    ]);

    app('log')
        ->debug('debug message', ['foo' => 'bar']);

    $fake->assertEventExists('debug message', [
        'context' => '{"foo":"bar"}',
    ]);
});

it('does not log the context when configured not to', function () {
    $fake = $this->withFakeSpan();
    $this->enableWatcher(LogWatcher::class, [
        'min_level' => 'debug',
        'record_context' => false,
    ]);

    app('log')
        ->debug('debug message', ['foo' => 'bar']);

    $fake->assertEventExists('debug message');
    $fake->assertEventAttributeMissing('debug message', 'context');
});

it('respects the max message length', function () {
    $fake = $this->withFakeSpan();
    $this->enableWatcher(LogWatcher::class, [
        'min_level' => 'debug',
        'record_context' => false,
        'max_message_length' => 5,
    ]);

    app('log')
        ->debug('debug message', ['foo' => 'bar']);

    $fake->assertEventExists('debug...');
});
