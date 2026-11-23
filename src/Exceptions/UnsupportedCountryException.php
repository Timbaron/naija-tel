<?php

declare(strict_types=1);

namespace NaijaTel\Exceptions;

class UnsupportedCountryException extends NaijaTelException
{
    public static function forCountry(string $country): self
    {
        return new self("The country code or ISO [{$country}] is not supported by NaijaTel. NaijaTel specializes in Nigerian telecommunications (+234 / NG).");
    }
}
