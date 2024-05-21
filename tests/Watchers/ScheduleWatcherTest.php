<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use PlunkettScott\LaravelOpenTelemetry\Watchers\ScheduleWatcher;

it('watches schedules', function () {
    $fake = $this->withFakeSpan();
    $this->enableWatcher(ScheduleWatcher::class, [
        'record_output' => true,
    ]);

    app(Schedule::class)->call(function () {})->everyMinute();
    Artisan::call('schedule:run');

    $fake->assertAttributeEquals(0, 'task.exit_code');
});
