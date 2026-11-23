<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

use Throwable;

class ProviderUnavailableException extends ProviderException
{
    public static function timeout(string $provider, float $seconds, ?Throwable $previous = null): self
    {
        return new self(
            "Provider timed out after {$seconds}s while requesting telecom lookup.",
            $provider,
            0,
            $previous
        );
    }

    public static function unreachable(string $provider, string $details = '', ?Throwable $previous = null): self
    {
        $message = 'Provider service is currently unreachable.';
        if ($details !== '') {
            $message .= " Details: {$details}";
        }

        return new self($message, $provider, 0, $previous);
    }
}
