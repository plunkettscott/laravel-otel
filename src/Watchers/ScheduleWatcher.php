<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Contracts\Foundation\Application;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use PlunkettScott\LaravelOpenTelemetry\CurrentSpan;

class ScheduleWatcher extends Watcher
{
    public ?string $optionsClass = ScheduleWatcherOptions::class;

    private SpanInterface $currentSpan;
    private ScopeInterface $currentScope;

    /**
     * @inheritDoc
     */
    public function register(Application $app): void
    {
        if (! $app->runningInConsole()) {
            return;
        }

        $app['events']->listen(ScheduledTaskStarting::class, [$this, 'recordTaskStarting']);
        $app['events']->listen(ScheduledTaskFailed::class, [$this, 'recordTaskFailed']);
        $app['events']->listen(ScheduledTaskFinished::class, [$this, 'recordTaskFinished']);
    }

    /**
     * Record a task starting.
     */
    public function recordTaskStarting(ScheduledTaskStarting $event): void
    {
        $this->currentSpan = $this->tracer->spanBuilder($this->getSpanName($event->task))
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttributes([
                'task.name' => $event->task->getSummaryForDisplay(),
                'task.expression' => $event->task->getExpression(),
                'task.timezone' => $event->task->timezone,
                'task.user' => $event->task->user,
                'task.without_overlapping' => $event->task->withoutOverlapping ? 'true' : 'false',
                'task.on_one_server' => $event->task->onOneServer ? 'true' : 'false',
                'task.even_in_maintenance_mode' => $event->task->evenInMaintenanceMode ? 'true' : 'false',
                'task.in_background' => $event->task->runInBackground ? 'true' : 'false',
            ])
            ->startSpan();
        $this->currentScope = $this->currentSpan->activate();

        if ($this->option('record_output')) {
            $event->task->onSuccessWithOutput(function ($output) {
                CurrentSpan::get()
                    ->setAttribute('task.output', (string) $output);
            }, true);
        }
    }

    /**
     * Record a task failed.
     */
    public function recordTaskFailed(ScheduledTaskFailed $event): void
    {
        if (! isset($this->currentSpan)) {
            return;
        }

        CurrentSpan::get()
            ->setAttribute('task.exit_code', $event->task->exitCode);

        $this->currentSpan->setStatus(StatusCode::STATUS_ERROR);
        $this->currentSpan->recordException($event->exception);

        $this->currentScope->detach();
        $this->currentSpan->end();

        unset($this->currentSpan);
        unset($this->currentScope);
    }

    /**
     * Record a task finished.
     */
    public function recordTaskFinished(ScheduledTaskFinished $event): void
    {
        if (! isset($this->currentSpan)) {
            return;
        }

        CurrentSpan::get()
            ->setAttributes([
                'task.exit_code', $event->task->exitCode,
            ]);

        $this->currentScope->detach();
        $this->currentSpan->end();

        unset($this->currentSpan);
        unset($this->currentScope);
    }

    /**
     * Get the span name for the given task.
     */
    private function getSpanName($task): string
    {
        return 'scheduled task ' . $task->getSummaryForDisplay();
    }
}
