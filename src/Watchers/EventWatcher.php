<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;

class EventWatcher extends Watcher
{
    public ?string $optionsClass = EventWatcherOptions::class;

    /**
     * @inheritDoc
     */
    public function register(Application $app): void
    {
        $app['events']->listen('*', [$this, 'recordEvent']);
    }

    /**
     * Record an event.
     */
    public function recordEvent($event, $payload): void
    {
        if ($this->shouldIgnore($event) || $this->eventIsFiredByLaravel($event)) {
            return;
        }

        CurrentSpan::get()
            ->addEvent(sprintf('Event "%s" fired.', $event), [
                'event.name' => $event,
                'event.broadcasts' => class_exists($event) &&
                    in_array(ShouldBroadcast::class, (array) class_implements($event)),
            ]);
    }

    private function eventIsFiredByLaravel($event): bool
    {
        return Str::is([
            'Illuminate\*',
            'Laravel\Octane\*',
            'Laravel\Scout\Events\ModelsImported',
            'eloquent*',
            'bootstrapped*',
            'bootstrapping*',
            'creating*',
            'composing*',
        ], $event);
    }

    private function shouldIgnore($event): bool
    {
        return Str::is($this->option('ignored', []), $event);
    }
}
