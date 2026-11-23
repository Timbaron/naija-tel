<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

class ConfigurationException extends NaijaTelException
{
    public static function missing(string $key): self
    {
        return new self("Missing required NaijaTel configuration key: [{$key}].");
    }

    public static function invalid(string $key, string $reason): self
    {
        return new self("Invalid configuration for [{$key}]: {$reason}");
    }
}
