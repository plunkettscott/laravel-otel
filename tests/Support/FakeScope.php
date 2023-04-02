<?php

namespace PlunkettScott\LaravelOpenTelemetry\Tests\Support;

use Exception;
use OpenTelemetry\Context\ScopeInterface;
use PHPUnit\Framework\AssertionFailedError;

class FakeScope implements ScopeInterface
{
    private $detached = false;

    /**
     * @inheritDoc
     */
    public function detach(): int
    {
        $this->detached = true;

        return 0;
    }

    /**
     * @throws Exception
     */
    public function assertDetached(): void
    {
        if (!$this->detached) {
            throw new AssertionFailedError('Scope was not detached');
        }
    }
}
