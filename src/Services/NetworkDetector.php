<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use Closure;
use Illuminate\Contracts\Container\Container;
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Contracts\NetworkDetectorInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Data\PhoneNumber;
use NaijaTel\Enums\Network;
use NaijaTel\Exceptions\InvalidPhoneNumberException;
use NaijaTel\Exceptions\UnsupportedCountryException;

class NetworkDetector implements NetworkDetectorInterface
{
    private ?string $temporaryProvider = null;

    public function __construct(
        private readonly PhoneNumberParserInterface $normalizer,
        private readonly PrefixDetector $prefixDetector,
        private readonly MnpLookupService $mnpLookupService,
        private readonly NetworkCache $cache,
        private readonly ProviderManager $providerManager
    ) {}

    /**
     * Detect the telecom network.
     * Default behavior is fast, free, offline prefix detection (cached).
     */
    public function detect(string $phoneNumber): NetworkLookupResult
    {
        $normalized = $this->normalizer->normalize($phoneNumber);

        return $this->cache->remember($normalized, 'prefix', function () use ($normalized) {
            return $this->prefixDetector->detect($normalized);
        });
    }

    /**
     * Strictly detect the original allocated network by prefix.
     * Never performs external network requests or MNP queries.
     */
    public function detectByPrefix(string $phoneNumber): NetworkLookupResult
    {
        $normalized = $this->normalizer->normalize($phoneNumber);

        return $this->prefixDetector->detect($normalized);
    }

    /**
     * Perform an authoritative MNP network lookup using the active or specified provider.
     */
    public function lookup(string $phoneNumber, ?string $provider = null): NetworkLookupResult
    {
        $providerToUse = $provider ?? $this->temporaryProvider;
        $this->temporaryProvider = null;

        return $this->mnpLookupService->lookup($phoneNumber, $providerToUse);
    }

    /**
     * Check if a number has been ported to another network.
     * Requires an authoritative MNP lookup.
     */
    public function isPorted(string $phoneNumber, ?string $provider = null): ?bool
    {
        $result = $this->lookup($phoneNumber, $provider);

        return $result->isPorted();
    }

    /**
     * Temporarily set the MNP provider to use for the next lookup call.
     */
    public function using(string $provider): self
    {
        $this->temporaryProvider = $provider;

        return $this;
    }

    /**
     * Register a custom MNP provider.
     *
     * @param  Closure(Container): MnpProviderInterface|class-string<MnpProviderInterface>  $creator
     */
    public function extend(string $name, Closure|string $creator): self
    {
        $this->providerManager->extend($name, $creator);

        return $this;
    }

    /**
     * Normalize a valid Nigerian phone number into canonical E.164 (+2348031234567).
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function normalize(string $phoneNumber): string
    {
        return $this->normalizer->normalize($phoneNumber);
    }

    /**
     * Parse input into a rich PhoneNumber DTO.
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function parse(string $phoneNumber): PhoneNumber
    {
        return $this->normalizer->parse($phoneNumber);
    }

    /**
     * Determine if a phone number is valid according to the Nigerian numbering plan.
     */
    public function isValid(string $phoneNumber): bool
    {
        return $this->normalizer->isValid($phoneNumber);
    }

    /**
     * Validate a phone number, throwing an exception if invalid.
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function validate(string $phoneNumber): void
    {
        $this->normalizer->validate($phoneNumber);
    }

    /**
     * Format a phone number in standard national format (0803 123 4567).
     */
    public function formatNational(string $phoneNumber): string
    {
        return $this->normalizer->formatNational($phoneNumber);
    }

    /**
     * Format a phone number in standard international format (+234 803 123 4567).
     */
    public function formatInternational(string $phoneNumber): string
    {
        return $this->normalizer->formatInternational($phoneNumber);
    }

    /**
     * Extract the country calling code (+234).
     */
    public function getCountryCode(string $phoneNumber): ?string
    {
        return $this->normalizer->getCountryCode($phoneNumber);
    }

    /**
     * Return list of supported telecom network definitions.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function getSupportedNetworks(): array
    {
        return [
            ['id' => Network::MTN->value, 'name' => Network::MTN->label()],
            ['id' => Network::AIRTEL->value, 'name' => Network::AIRTEL->label()],
            ['id' => Network::GLO->value, 'name' => Network::GLO->label()],
            ['id' => Network::NINE_MOBILE->value, 'name' => Network::NINE_MOBILE->label()],
        ];
    }

    /**
     * Clear cached lookup entries for a specific phone number.
     */
    public function clearCache(string $phoneNumber): bool
    {
        if ($this->normalizer->isValid($phoneNumber)) {
            $normalized = $this->normalizer->normalize($phoneNumber);

            return $this->cache->forget($normalized);
        }

        return false;
    }

    /**
     * Invalidate all cached NaijaTel lookup entries safely without affecting other app caches.
     */
    public function clearAllCache(): bool
    {
        return $this->cache->flushAll();
    }
}
