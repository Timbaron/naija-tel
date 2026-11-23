<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

class UnsupportedNetworkException extends NaijaTelException
{
    public static function forIdentifier(string $identifier): self
    {
        return new self("The network identifier [{$identifier}] is not recognized or supported by NaijaTel.");
    }
}
