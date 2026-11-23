<?php

declare(strict_types=1);

namespace NaijaTel\Providers\Icn;

use DateTimeImmutable;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Exceptions\ConfigurationException;
use NaijaTel\Exceptions\ProviderException;
use NaijaTel\Exceptions\ProviderUnavailableException;
use NaijaTel\Support\NetworkPrefixRepository;
use NaijaTel\Support\PhoneNumberFormatter;

/**
 * Adapter for Interconnect Clearinghouse Nigeria (ICN) Number Lookup Service.
 *
 * NOTE: Interconnect Clearinghouse Nigeria (ICN) is the central custodian for
 * Mobile Number Portability (MNP) routing data in Nigeria. Production access requires
 * an enterprise service-level agreement with ICN (https://interconnectnigeria.com).
 * In the absence of signed commercial credentials and IP whitelisting, live requests
 * cannot be dispatched to ICN.
 */
class IcnMnpProvider implements MnpProviderInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly NetworkPrefixRepository $prefixRepository,
        private readonly PhoneNumberParserInterface $normalizer,
        private readonly array $config = []
    ) {}

    public function getName(): string
    {
        return 'icn';
    }

    /**
     * Execute an authoritative MNP lookup against ICN Number Lookup API.
     *
     * @throws ProviderException
     * @throws ProviderUnavailableException
     * @throws ConfigurationException
     */
    public function lookup(string $phoneNumber): NetworkLookupResult
    {
        $this->ensureConfigured();

        $normalized = $this->normalizer->normalize($phoneNumber);
        $originalNetwork = $this->prefixRepository->match($normalized);

        $baseUrl = rtrim((string) $this->config['base_url'], '/');
        $apiKey = (string) $this->config['api_key'];
        $timeout = (float) ($this->config['timeout'] ?? 10.0);
        $connectTimeout = (float) ($this->config['connect_timeout'] ?? 5.0);

        try {
            $response = $this->http
                ->timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post("{$baseUrl}/lookup", [
                    'phone_number' => $normalized,
                ]);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new ProviderException(
                    'Authentication failed with ICN Number Lookup API. Check API credentials.',
                    $this->getName(),
                    $response->status()
                );
            }

            if ($response->status() === 429) {
                throw new ProviderException(
                    'Rate limit exceeded on ICN Number Lookup API.',
                    $this->getName(),
                    429
                );
            }

            if ($response->failed()) {
                throw new ProviderException(
                    "ICN API returned HTTP error [{$response->status()}]: ".$response->body(),
                    $this->getName(),
                    $response->status()
                );
            }

            $json = $response->json();
            if (! is_array($json)) {
                throw new ProviderException(
                    'ICN API returned an invalid or unparseable JSON payload.',
                    $this->getName()
                );
            }

            return $this->parseResponse($json, $normalized, $originalNetwork);
        } catch (ConnectionException $e) {
            throw ProviderUnavailableException::unreachable(
                $this->getName(),
                "Could not connect to ICN endpoint [{$baseUrl}]: ".$e->getMessage(),
                $e
            );
        } catch (RequestException $e) {
            throw new ProviderException(
                'HTTP request error with ICN: '.$e->getMessage(),
                $this->getName(),
                $e->getCode(),
                $e
            );
        } catch (ProviderException|ProviderUnavailableException $e) {
            throw $e;
        } catch (Exception $e) {
            $masked = PhoneNumberFormatter::mask($normalized);
            throw new ProviderException(
                "Unexpected failure looking up number [{$masked}]: ".$e->getMessage(),
                $this->getName(),
                0,
                $e
            );
        }
    }

    /**
     * Parse provider JSON response into a normalized NetworkLookupResult.
     *
     * @param  array<string, mixed>  $payload
     */
    private function parseResponse(array $payload, string $normalized, Network $originalNetwork): NetworkLookupResult
    {
        // Extract network identifier safely across expected naming conventions
        $networkRaw = (string) ($payload['network'] ?? $payload['serving_network'] ?? $payload['operator'] ?? 'unknown');
        $currentNetwork = Network::tryFromString($networkRaw);

        // Extract or compute porting status
        $isPorted = null;
        if (array_key_exists('is_ported', $payload)) {
            $isPorted = (bool) $payload['is_ported'];
        } elseif (array_key_exists('ported', $payload)) {
            $isPorted = (bool) $payload['ported'];
        } elseif ($currentNetwork->isKnown() && $originalNetwork->isKnown()) {
            $isPorted = $currentNetwork !== $originalNetwork;
        }

        $status = $currentNetwork->isKnown() ? LookupStatus::VERIFIED : LookupStatus::UNKNOWN;

        return new NetworkLookupResult(
            phoneNumber: $normalized,
            network: $currentNetwork,
            originalNetwork: $originalNetwork->isKnown() ? $originalNetwork : null,
            isPorted: $isPorted,
            verified: true,
            method: DetectionMethod::MNP,
            status: $status,
            provider: $this->getName(),
            lookupAt: new DateTimeImmutable,
            metadata: [
                'provider' => 'icn',
                'raw_status' => $payload['status'] ?? null,
            ],
        );
    }

    /**
     * Ensure provider is enabled and configured.
     *
     * @throws ConfigurationException
     */
    private function ensureConfigured(): void
    {
        $enabled = (bool) ($this->config['enabled'] ?? false);
        if (! $enabled) {
            throw ConfigurationException::invalid(
                'providers.icn.enabled',
                'ICN MNP provider is currently disabled. Configure valid credentials and enable in config/naija-tel.php.'
            );
        }

        if (empty($this->config['base_url'])) {
            throw ConfigurationException::missing('providers.icn.base_url');
        }

        if (empty($this->config['api_key'])) {
            throw ConfigurationException::missing('providers.icn.api_key');
        }
    }
}
