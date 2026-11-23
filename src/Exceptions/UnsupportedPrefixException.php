<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

class UnsupportedPrefixException extends NaijaTelException
{
    public static function forPrefix(string $prefix): self
    {
        return new self("The prefix [{$prefix}] is not recognized in the Nigerian National Numbering Plan.");
    }
}
