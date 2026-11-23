<?php

declare(strict_types=1);

namespace NaijaTel\Contracts;

use NaijaTel\Data\NetworkLookupResult;

interface MnpProviderInterface
{
    /**
     * Perform an authoritative MNP network lookup for the given phone number.
     *
     * @param  string  $phoneNumber  E.164 or national format phone number
     */
    public function lookup(string $phoneNumber): NetworkLookupResult;

    /**
     * Get the identifier name of the provider.
     */
    public function getName(): string;
}
