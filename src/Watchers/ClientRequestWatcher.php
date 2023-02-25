<?php

namespace PlunkettScott\LaravelOpenTelemetry\Watchers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ClientRequestWatcher extends Watcher
{
    public ?string $optionsClass = ClientRequestWatcherOptions::class;

    /**
     * @var array<string, SpanInterface>
     */
    protected array $spans = [];

    /**
     * {@inheritDoc}
     */
    public function register(Application $app): void
    {
        $app['events']->listen(RequestSending::class, [$this, 'recordRequest']);
        $app['events']->listen(ConnectionFailed::class, [$this, 'recordConnectionFailed']);
        $app['events']->listen(ResponseReceived::class, [$this, 'recordResponse']);
    }

    /**
     * Record a request.
     */
    public function recordRequest(RequestSending $request): void
    {
        $parsedUrl = collect(parse_url($request->request->url()));
        $processedUrl = $parsedUrl->get('scheme').'://'.$parsedUrl->get('host').$parsedUrl->get('path', '');

        if ($parsedUrl->has('query')) {
            $processedUrl .= '?' . $parsedUrl->get('query');
        }

        $span = $this->tracer->spanBuilder('http '.$request->request->method().' '.$request->request->url())
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttributes([
                'http.method' => $request->request->method(),
                'http.url' => $processedUrl,
                'http.target' => $parsedUrl['path'] ?? '',
                'http.host' => $parsedUrl['host'] ?? '',
                'http.scheme' => $parsedUrl['scheme'] ?? '',
                'net.peer.name' => $parsedUrl['host'] ?? '',
                'net.peer.port' => $parsedUrl['port'] ?? '',
            ])
            ->startSpan();

        $this->spans[$this->createRequestComparisonHash($request->request)] = $span;
    }

    /**
     * Record a connection failure.
     */
    public function recordConnectionFailed(ConnectionFailed $request): void
    {
        $requestHash = $this->createRequestComparisonHash($request->request);

        $span = $this->spans[$requestHash] ?? null;
        if (is_null($span)) {
            return;
        }

        $span->setStatus(StatusCode::STATUS_ERROR, 'Connection failed');
        $span->end();

        unset($this->spans[$requestHash]);
    }

    /**
     * Record a response.
     */
    public function recordResponse(ResponseReceived $request): void
    {
        $requestHash = $this->createRequestComparisonHash($request->request);

        $span = $this->spans[$requestHash] ?? null;
        if (is_null($span)) {
            return;
        }

        $span->setAttributes([
            'http.status_code' => $request->response->status(),
            'http.status_text' => HttpResponse::$statusTexts[$request->response->status()] ?? '',
            'http.response_content_length' => $request->response->header('Content-Length') ?? null,
            'http.response_content_type' => $request->response->header('Content-Type') ?? null,
        ]);

        $this->maybeRecordError($span, $request->response);
        $span->end();

        unset($this->spans[$requestHash]);
    }

    private function createRequestComparisonHash(Request $request): string
    {
        return sha1($request->method().'|'.$request->url().'|'.$request->body());
    }

    private function maybeRecordError(SpanInterface $span, Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        if (! $this->option('record_errors', true)) {
            return;
        }

        if (in_array($response->status(), $this->option('record_errors_except_statuses', []))) {
            return;
        }

        $span->setStatus(StatusCode::STATUS_ERROR,
            HttpResponse::$statusTexts[$response->status()] ?? $response->status());
    }
}
