<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

use Exception;

class NaijaTelException extends Exception
{
    /**
     * Helper to mask a phone number for privacy in exception messages.
     */
    protected static function mask(string $phoneNumber): string
    {
        $len = strlen($phoneNumber);
        if ($len <= 4) {
            return '****';
        }

        $visibleStart = (int) min(4, floor($len / 3));
        $visibleEnd = (int) min(3, floor($len / 4));
        $maskedLen = $len - $visibleStart - $visibleEnd;

        return substr($phoneNumber, 0, $visibleStart)
            .str_repeat('*', max(1, $maskedLen))
            .substr($phoneNumber, -$visibleEnd);
    }
}
