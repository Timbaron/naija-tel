<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use DateTimeImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Exceptions\ProviderException;
use NaijaTel\Exceptions\ProviderUnavailableException;
use NaijaTel\Support\PhoneNumberFormatter;
use Psr\Log\LoggerInterface;
use Throwable;

class MnpLookupService
{
    public function __construct(
        private readonly ProviderManager $providerManager,
        private readonly PrefixDetector $prefixDetector,
        private readonly NetworkCache $cache,
        private readonly PhoneNumberParserInterface $normalizer,
        private readonly ConfigRepository $config,
        private readonly ?LoggerInterface $logger = null
    ) {}

    /**
     * Perform an authoritative MNP lookup for a phone number using the configured provider.
     *
     * @throws ProviderException
     * @throws ProviderUnavailableException
     */
    public function lookup(string $phoneNumber, ?string $providerName = null): NetworkLookupResult
    {
        $normalized = $this->normalizer->normalize($phoneNumber);

        return $this->cache->remember($normalized, 'mnp', function () use ($normalized, $providerName) {
            $provider = $this->providerManager->provider($providerName);

            $this->logInfo("Initiating MNP lookup for number [{number}] via provider [{$provider->getName()}].", $normalized);

            try {
                $result = $provider->lookup($normalized);

                $this->logInfo("MNP lookup successful for [{number}]: resolved to [{$result->network->value}], ported: [{$result->isPorted}].", $normalized);

                return $result;
            } catch (Throwable $e) {
                $this->logError("MNP lookup failed for [{number}] via provider [{$provider->getName()}]: ".$e->getMessage(), $normalized);

                if ($this->isFallbackEnabled()) {
                    return $this->createFallbackResult($normalized, $provider->getName(), $e);
                }

                throw $e;
            }
        });
    }

    /**
     * Determine if fallback to prefix detection is enabled.
     */
    public function isFallbackEnabled(): bool
    {
        return (bool) $this->config->get('naija-tel.fallback.enabled', true);
    }

    /**
     * Create an explicitly unverified fallback result from prefix detection.
     * A provider failure must NEVER become a verified result.
     */
    private function createFallbackResult(string $normalized, string $providerName, Throwable $error): NetworkLookupResult
    {
        $prefixResult = $this->prefixDetector->detect($normalized);

        return new NetworkLookupResult(
            phoneNumber: $normalized,
            network: $prefixResult->network,
            originalNetwork: $prefixResult->originalNetwork,
            isPorted: null, // Unknown because MNP failed
            verified: false, // Never verified on provider failure
            method: DetectionMethod::PREFIX,
            status: LookupStatus::PROVIDER_UNAVAILABLE,
            provider: $providerName,
            lookupAt: new DateTimeImmutable,
            metadata: [
                'fallback_used' => true,
                'provider_error' => $error->getMessage(),
                'provider_name' => $providerName,
            ],
        );
    }

    /**
     * Log info message with optional phone number masking.
     */
    private function logInfo(string $message, string $phoneNumber): void
    {
        if (! $this->isLoggingEnabled() || $this->logger === null) {
            return;
        }

        $formatted = $this->maskNumberIfConfigured($phoneNumber);
        $this->logger->info(str_replace('{number}', $formatted, $message));
    }

    /**
     * Log error message with optional phone number masking.
     */
    private function logError(string $message, string $phoneNumber): void
    {
        if (! $this->isLoggingEnabled() || $this->logger === null) {
            return;
        }

        $formatted = $this->maskNumberIfConfigured($phoneNumber);
        $this->logger->error(str_replace('{number}', $formatted, $message));
    }

    private function isLoggingEnabled(): bool
    {
        return (bool) $this->config->get('naija-tel.logging.enabled', true);
    }

    private function maskNumberIfConfigured(string $phoneNumber): string
    {
        $mask = (bool) $this->config->get('naija-tel.logging.mask_phone_numbers', true);

        return $mask ? PhoneNumberFormatter::mask($phoneNumber) : $phoneNumber;
    }
}
