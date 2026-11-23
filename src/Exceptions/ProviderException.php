<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

use Throwable;

class ProviderException extends NaijaTelException
{
    public function __construct(
        string $message,
        private readonly string $provider = 'unknown',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("[Provider: {$this->provider}] {$message}", $code, $previous);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
