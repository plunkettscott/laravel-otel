<?php

namespace PlunkettScott\LaravelOpenTelemetry;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\SamplerInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SDK\Trace\TracerProviderInterface;
use OpenTelemetry\SemConv\ResourceAttributes;

class OtelApplicationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! config('otel.enabled')) {
            return;
        }

        $this->registerTracerProvider();
    }

    public function sampler(): SamplerInterface
    {
        return new AlwaysOnSampler();
    }

    public function resourceInfo(): ResourceInfo
    {
        return ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => config('app.name', 'laravel'),
            ResourceAttributes::DEPLOYMENT_ENVIRONMENT => config('app.env', 'production'),
        ]));
    }

    /**
     * @return array<SpanProcessorInterface>
     */
    public function spanProcessors(): array
    {
        return [];
    }

    protected function registerTracerProvider(): void
    {
        $this->app->singleton(
            TracerProviderInterface::class,
            function () {
                $tracerProvider = TracerProvider::builder()
                    ->setSampler(sampler: $this->sampler())
                    ->setResource(ResourceInfoFactory::merge(
                        $this->resourceInfo(),
                        ResourceInfoFactory::defaultResource(),
                    ));

                foreach ($this->spanProcessors() as $spanProcessor) {
                    $tracerProvider->addSpanProcessor($spanProcessor);
                }

                return $tracerProvider->build();
            },
        );
    }
}
