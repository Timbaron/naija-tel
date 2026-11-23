<?php

declare(strict_types=1);

namespace NaijaTel\Enums;

enum LookupStatus: string
{
    case VERIFIED = 'verified';
    case INFERRED = 'inferred';
    case UNKNOWN = 'unknown';
    case INVALID = 'invalid';
    case PROVIDER_UNAVAILABLE = 'provider_unavailable';

    /**
     * Determine whether the lookup status represents a verified result.
     */
    public function isVerified(): bool
    {
        return $this === self::VERIFIED;
    }

    /**
     * Determine whether the lookup status indicates an operational failure.
     */
    public function isFailure(): bool
    {
        return $this === self::PROVIDER_UNAVAILABLE || $this === self::INVALID;
    }
}
