<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\SDK\Common\Time\ClockFactory;
use OpenTelemetry\SemConv\TraceAttributes;

class QueryWatcher extends Watcher
{
    public ?string $optionsClass = QueryWatcherOptions::class;

    /**
     * {@inheritDoc}
     */
    public function register(Application $app): void
    {
        $app['events']->listen(QueryExecuted::class, [$this, 'recordQuery']);
    }

    /**
     * Record a query.
     */
    public function recordQuery(QueryExecuted $query): void
    {
        if (! $this->shouldRecordStatement($query->sql)) {
            return;
        }

        $nowInNs = ClockFactory::getDefault()->now();

        $operationName = Str::upper(Str::before($query->sql, ' '));
        if (! in_array($operationName, ['SELECT', 'INSERT', 'UPDATE', 'DELETE'])) {
            $operationName = null;
        }

        $span = $this->tracer->spanBuilder('sql '.$operationName ?? 'sql')
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setStartTimestamp($this->calculateQueryStartTime($nowInNs, $query->time))
            ->startSpan();

        $attributes = [
            TraceAttributes::DB_SYSTEM => $query->connection->getDriverName(),
            TraceAttributes::DB_NAME => $query->connection->getDatabaseName(),
            TraceAttributes::DB_OPERATION => $operationName,
            TraceAttributes::DB_USER => $query->connection->getConfig('username'),
        ];

        if ($this->option('record_sql', true)) {
            $attributes[TraceAttributes::DB_STATEMENT] = $query->sql;
        }

        $span->setAttributes($attributes);
        $span->end($nowInNs);
    }

    private function calculateQueryStartTime(int $nowInNs, int $queryTimeMs): int
    {
        return intval($nowInNs - ($queryTimeMs * 1000000));
    }

    private function shouldRecordStatement(string $statement): bool
    {
        foreach ($this->option('ignore_sql_strings', []) as $str) {
            if (Str::contains($statement, $str, true)) {
                return false;
            }
        }

        foreach ($this->option('ignore_sql_regex', []) as $regexp) {
            if (preg_match($regexp, $statement)) {
                return false;
            }
        }

        return true;
    }
}
