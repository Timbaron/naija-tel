<?php

declare(strict_types=1);

namespace NaijaTel\Enums;

enum DetectionMethod: string
{
    case PREFIX = 'prefix';
    case MNP = 'mnp';
    case CUSTOM = 'custom';
    case UNKNOWN = 'unknown';

    /**
     * Determine whether the detection method is authoritative/verified.
     */
    public function isAuthoritative(): bool
    {
        return $this === self::MNP || $this === self::CUSTOM;
    }
}
