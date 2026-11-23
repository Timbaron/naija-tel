<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;

class NetworkCache
{
    private ?CacheRepository $store = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly CacheFactory $cacheFactory,
        private readonly array $config = []
    ) {}

    /**
     * Check if caching is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    /**
     * Get or store a lookup result in cache.
     *
     * @param  Closure(): NetworkLookupResult  $callback
     */
    public function remember(string $normalizedNumber, string $type, Closure $callback): NetworkLookupResult
    {
        if (! $this->isEnabled()) {
            return $callback();
        }

        $key = $this->buildKey($normalizedNumber, $type);
        $ttl = $this->getTtl($type);

        $cached = $this->getStore()->get($key);
        if (is_array($cached)) {
            return $this->deserializeResult($cached);
        }

        $result = $callback();

        // Never cache provider failures or invalid numbers as successful results
        if ($result->isSuccess()) {
            $this->getStore()->put($key, $result->toArray(), $ttl);
        }

        return $result;
    }

    /**
     * Invalidate cached results for a specific phone number.
     */
    public function forget(string $normalizedNumber): bool
    {
        $prefixKey = $this->buildKey($normalizedNumber, 'prefix');
        $mnpKey = $this->buildKey($normalizedNumber, 'mnp');

        $store = $this->getStore();
        $store->forget($prefixKey);
        $store->forget($mnpKey);

        return true;
    }

    /**
     * Invalidate all NaijaTel cached items safely by incrementing cache version.
     */
    public function flushAll(): bool
    {
        $versionKey = $this->getVersionKey();
        $currentVersion = (int) ($this->getStore()->get($versionKey) ?? 1);

        return $this->getStore()->put($versionKey, $currentVersion + 1, 86400 * 365);
    }

    /**
     * Build deterministic cryptographic cache key for a normalized phone number.
     * Never stores raw phone numbers in cache keys for privacy/PII compliance.
     */
    public function buildKey(string $normalizedNumber, string $type): string
    {
        $prefix = (string) ($this->config['prefix'] ?? 'naija_tel');
        $version = $this->getCurrentVersion();
        $hash = hash('sha256', $normalizedNumber);

        return "{$prefix}:v{$version}:{$type}:{$hash}";
    }

    /**
     * Get TTL in seconds for the given lookup type.
     */
    public function getTtl(string $type): int
    {
        $ttls = $this->config['ttl'] ?? [];

        return match ($type) {
            'mnp' => (int) ($ttls['mnp'] ?? 3600),
            'prefix' => (int) ($ttls['prefix'] ?? 86400),
            default => 3600,
        };
    }

    /**
     * Get current cache version number.
     */
    private function getCurrentVersion(): int
    {
        $versionKey = $this->getVersionKey();

        return (int) ($this->getStore()->get($versionKey) ?? 1);
    }

    private function getVersionKey(): string
    {
        $prefix = (string) ($this->config['prefix'] ?? 'naija_tel');

        return "{$prefix}:version";
    }

    private function getStore(): CacheRepository
    {
        if ($this->store === null) {
            $storeName = $this->config['store'] ?? null;
            $this->store = $this->cacheFactory->store($storeName);
        }

        return $this->store;
    }

    /**
     * Deserialize cached array data back to NetworkLookupResult.
     *
     * @param  array<string, mixed>  $data
     */
    private function deserializeResult(array $data): NetworkLookupResult
    {
        return new NetworkLookupResult(
            phoneNumber: (string) ($data['phone_number'] ?? ''),
            network: Network::tryFromString((string) ($data['network'] ?? 'unknown')),
            originalNetwork: isset($data['original_network'])
                ? Network::tryFromString((string) $data['original_network'])
                : null,
            isPorted: isset($data['is_ported']) ? (bool) $data['is_ported'] : null,
            verified: (bool) ($data['verified'] ?? false),
            method: DetectionMethod::tryFrom((string) ($data['method'] ?? 'prefix')) ?? DetectionMethod::PREFIX,
            status: LookupStatus::tryFrom((string) ($data['status'] ?? 'inferred')) ?? LookupStatus::INFERRED,
            provider: isset($data['provider']) ? (string) $data['provider'] : null,
            lookupAt: isset($data['lookup_at']) ? new DateTimeImmutable((string) $data['lookup_at']) : new DateTimeImmutable,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }
}
