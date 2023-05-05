<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SemConv\TraceAttributes;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;

class RedisWatcher extends Watcher
{
    public ?string $optionsClass = RedisWatcherOptions::class;

    /**
     * {@inheritDoc}
     */
    public function register(Application $app): void
    {
        if (! $app->bound('redis')) {
            return;
        }

        $app['events']->listen(CommandExecuted::class, [$this, 'recordCommand']);

        foreach ((array) $app['redis']->connections() as $connection) {
            $connection->setEventDispatcher($app['events']);
        }

        $app['redis']->enableEvents();
    }

    /**
     * Record a command.
     */
    public function recordCommand(CommandExecuted $command): void
    {
        if ($this->shouldIgnore($command)) {
            return;
        }

        if (! CurrentSpan::get()->isRecording()) {
            return;
        }

        $nowInNs = ClockFactory::getDefault()->now();
        $recordCommand = $this->option('record_command', true);

        $span = $this->tracer->spanBuilder($recordCommand ? 'redis '.Str::upper($command->command) : 'redis')
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setStartTimestamp($this->calculateQueryStartTime($nowInNs, $command->time))
            ->startSpan();

        $span->setAttribute(TraceAttributes::DB_SYSTEM, 'redis');
        $span->setAttribute(TraceAttributes::DB_REDIS_DATABASE_INDEX, $command->connectionName);

        if ($recordCommand) {
            $span->setAttribute(TraceAttributes::DB_STATEMENT, Str::upper($command->command));
        }

        $span->end($nowInNs);
    }

    private function calculateQueryStartTime(float $nowInNs, float $queryTimeMs): int
    {
        return intval($nowInNs - ($queryTimeMs * 1000000), 10);
    }

    private function shouldIgnore(CommandExecuted $command): bool
    {
        return in_array($command->command, $this->option('ignore_commands', [
            'pipeline',
            'transaction',
        ]));
    }
}
