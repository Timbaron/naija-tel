<?php

declare(strict_types=1);

namespace NaijaTel\Providers;

use DateTimeImmutable;
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Exceptions\ProviderUnavailableException;
use NaijaTel\Support\NetworkPrefixRepository;

class FakeMnpProvider implements MnpProviderInterface
{
    /**
     * @var array<string, array{network: Network, original_network: ?Network, is_ported: bool}>
     */
    private array $simulatedNumbers = [];

    /**
     * @var array<int, string>
     */
    private array $lookupHistory = [];

    private bool $simulateTimeout = false;

    private ?string $simulateError = null;

    public function __construct(
        private readonly NetworkPrefixRepository $prefixRepository,
        private readonly PhoneNumberParserInterface $normalizer
    ) {}

    public function getName(): string
    {
        return 'fake';
    }

    /**
     * Define a simulated lookup response for a specific phone number.
     */
    public function setPortedNumber(
        string $phoneNumber,
        Network $currentNetwork,
        ?Network $originalNetwork = null
    ): self {
        $normalized = $this->normalizer->normalize($phoneNumber);
        $original = $originalNetwork ?? $this->prefixRepository->match($normalized);

        $this->simulatedNumbers[$normalized] = [
            'network' => $currentNetwork,
            'original_network' => $original,
            'is_ported' => $currentNetwork !== $original,
        ];

        return $this;
    }

    /**
     * Simulate a provider timeout.
     */
    public function simulateTimeout(bool $simulate = true): self
    {
        $this->simulateTimeout = $simulate;

        return $this;
    }

    /**
     * Simulate a provider error.
     */
    public function simulateError(?string $errorMessage = null): self
    {
        $this->simulateError = $errorMessage;

        return $this;
    }

    /**
     * Perform the simulated MNP lookup.
     *
     * @throws ProviderUnavailableException
     */
    public function lookup(string $phoneNumber): NetworkLookupResult
    {
        $normalized = $this->normalizer->normalize($phoneNumber);
        $this->lookupHistory[] = $normalized;

        if ($this->simulateTimeout) {
            throw ProviderUnavailableException::timeout($this->getName(), 5.0);
        }

        if ($this->simulateError !== null) {
            throw ProviderUnavailableException::unreachable($this->getName(), $this->simulateError);
        }

        if (isset($this->simulatedNumbers[$normalized])) {
            $sim = $this->simulatedNumbers[$normalized];

            return new NetworkLookupResult(
                phoneNumber: $normalized,
                network: $sim['network'],
                originalNetwork: $sim['original_network'],
                isPorted: $sim['is_ported'],
                verified: true,
                method: DetectionMethod::MNP,
                status: LookupStatus::VERIFIED,
                provider: $this->getName(),
                lookupAt: new DateTimeImmutable,
                metadata: ['simulated' => true],
            );
        }

        // By default, simulate non-ported number based on allocated prefix
        $original = $this->prefixRepository->match($normalized);

        return new NetworkLookupResult(
            phoneNumber: $normalized,
            network: $original,
            originalNetwork: $original,
            isPorted: false,
            verified: true,
            method: DetectionMethod::MNP,
            status: LookupStatus::VERIFIED,
            provider: $this->getName(),
            lookupAt: new DateTimeImmutable,
            metadata: ['simulated' => true, 'default_non_ported' => true],
        );
    }

    /**
     * Get lookup call history.
     *
     * @return array<int, string>
     */
    public function getLookupHistory(): array
    {
        return $this->lookupHistory;
    }

    /**
     * Clear all configured simulations and history.
     */
    public function reset(): void
    {
        $this->simulatedNumbers = [];
        $this->lookupHistory = [];
        $this->simulateTimeout = false;
        $this->simulateError = null;
    }
}
