<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobReleasedAfterException;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;
use PlunkettScott\LaravelOpenTelemetry\Contracts\NotTraceAware;
use PlunkettScott\LaravelOpenTelemetry\Contracts\TraceAware;
use ReflectionClass;
use ReflectionException;

class QueueWatcher extends Watcher
{
    public ?string $optionsClass = QueueWatcherOptions::class;

    private SpanInterface $currentSpan;
    private ScopeInterface $currentScope;

    /**
     * @inheritDoc
     */
    public function register(Application $app): void
    {
        $app['queue']->createPayloadUsing(function ($connection, $queue, $payload) {
            return $this->createQueuePayload($connection, $queue, $payload);
        });

        if (! $app->runningInConsole()) {
            return;
        }

        $app['events']->listen(JobProcessing::class, [$this, 'recordJobProcessing']);
        $app['events']->listen(JobProcessed::class, [$this, 'recordJobProcessed']);
        $app['events']->listen(JobExceptionOccurred::class, [$this, 'recordJobExceptionOccurred']);
        $app['events']->listen(JobReleasedAfterException::class, [$this, 'recordJobReleasedAfterException']);
        $app['events']->listen(JobRetryRequested::class, [$this, 'recordJobRetryRequested']);
        $app['events']->listen(JobFailed::class, [$this, 'recordJobFailed']);
    }

    public function recordJobProcessing(JobProcessing $event): void
    {
        $spanContext = TraceContextPropagator::getInstance()
            ->extract($event->job->payload());

        $this->currentSpan = $this->tracer->spanBuilder($this->getSpanName($event->job))
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setParent($spanContext)
            ->setAttributes([
                'job.id' => $event->job->getJobId(),
                'job.uuid' => $event->job->uuid(),
                'job.name' => $event->job->resolveName(),
                'job.queue' => $event->job->getQueue(),
                'job.connection' => $event->job->getConnectionName(),
                'job.attempts' => $event->job->attempts(),
                'job.max_tries' => $event->job->maxTries(),
                'job.max_exceptions' => $event->job->maxExceptions(),
                'job.retry_until' => $event->job->retryUntil(),
                'job.timeout' => $event->job->timeout(),
            ])
            ->startSpan();
        $this->currentScope = $this->currentSpan->activate();
    }

    public function recordJobProcessed(JobProcessed $event): void
    {
        if (! isset($this->currentSpan) || ! isset($this->currentScope)) {
            return;
        }

        $this->currentScope->detach();
        $this->currentSpan->end();

        unset($this->currentSpan);
        unset($this->currentScope);
    }

    public function recordJobExceptionOccurred(JobExceptionOccurred $event): void
    {
        if (! isset($this->currentSpan) || ! isset($this->currentScope)) {
            return;
        }

        $this->currentScope->detach();
        $this->currentSpan->setStatus(StatusCode::STATUS_ERROR);
        $this->currentSpan->recordException($event->exception);
        $this->currentSpan->end();

        unset($this->currentSpan);
        unset($this->currentScope);
    }

    public function recordJobFailed(JobFailed $event): void
    {
        if (! isset($this->currentSpan) || ! isset($this->currentScope)) {
            return;
        }

        $this->currentScope->detach();
        $this->currentSpan->setStatus(StatusCode::STATUS_ERROR);
        $this->currentSpan->recordException($event->exception);
        $this->currentSpan->end();

        unset($this->currentSpan);
        unset($this->currentScope);
    }

    private function createQueuePayload($connection, $queue, $payload): array
    {
        if (! $this->shouldBeTraced($payload)) {
            return [];
        }

        $tracePayload = [];
        TraceContextPropagator::getInstance()
            ->inject($tracePayload);

        return $tracePayload;
    }

    private function shouldBeTraced(array $payload): bool
    {
        try {
            $reflection = new ReflectionClass($payload['data']['command']);
        } catch (ReflectionException $e) {
            return false;
        }

        if (Str::is($this->option('ignored', []), $reflection->getName())) {
            return false;
        }

        if ($reflection->implementsInterface(TraceAware::class)) {
            return true;
        }

        if ($reflection->implementsInterface(NotTraceAware::class)) {
            return false;
        }

        return $this->option('trace_by_default', true);
    }

    private function getSpanName(Job $job): string
    {
        $name = 'job ';

        if ($job->attempts() > 1) {
            $name .= "retry ";
        }

        if (method_exists($job, 'payload')) {
            $name .= $job->payload()['displayName'] ?? get_class($job);
            return $name;
        }

        return $name . get_class($job);
    }
}
