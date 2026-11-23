<?php

declare(strict_types=1);

namespace NaijaTel\Contracts;

use NaijaTel\Data\NetworkLookupResult;

interface NetworkDetectorInterface
{
    /**
     * Detect telecom network. Default behavior is fast, free, offline prefix detection
     * unless configured or requested otherwise.
     */
    public function detect(string $phoneNumber): NetworkLookupResult;

    /**
     * Strictly detect the original allocated network by prefix (always free, offline, local).
     */
    public function detectByPrefix(string $phoneNumber): NetworkLookupResult;

    /**
     * Perform an authoritative MNP lookup using configured provider.
     */
    public function lookup(string $phoneNumber): NetworkLookupResult;
}
