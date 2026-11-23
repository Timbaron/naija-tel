<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use DateTimeImmutable;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Support\NetworkPrefixRepository;

class PrefixDetector
{
    public function __construct(
        private readonly NetworkPrefixRepository $prefixRepository,
        private readonly PhoneNumberParserInterface $normalizer
    ) {}

    /**
     * Infer the original telecom network based purely on the mobile prefix allocation.
     * Always local, free, and offline. Never performs external requests.
     */
    public function detect(string $phoneNumber): NetworkLookupResult
    {
        $normalized = $this->normalizer->normalize($phoneNumber);
        $nsn = substr($normalized, 4); // E.g. "8031234567"
        $national = '0'.$nsn;

        $network = $this->prefixRepository->match($national);

        $status = $network->isKnown() ? LookupStatus::INFERRED : LookupStatus::UNKNOWN;

        return new NetworkLookupResult(
            phoneNumber: $normalized,
            network: $network,
            originalNetwork: $network->isKnown() ? $network : null,
            isPorted: null, // Prefix detection cannot determine whether a number has been ported
            verified: false, // Never verified/authoritative
            method: DetectionMethod::PREFIX,
            status: $status,
            provider: 'prefix',
            lookupAt: new DateTimeImmutable,
            metadata: [
                'prefix' => substr($national, 0, 4),
                'source' => 'ncc_numbering_plan',
            ],
        );
    }
}
