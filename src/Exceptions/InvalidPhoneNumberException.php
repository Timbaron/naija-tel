<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

class InvalidPhoneNumberException extends NaijaTelException
{
    public static function forNumber(string $phoneNumber, string $reason = 'Invalid format'): self
    {
        $masked = self::mask($phoneNumber);

        return new self("The phone number [{$masked}] is invalid: {$reason}.");
    }
}
