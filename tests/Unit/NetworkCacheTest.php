<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepo;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Mockery;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Services\NetworkCache;
use PHPUnit\Framework\TestCase;

class NetworkCacheTest extends TestCase
{
    private CacheRepo $store;

    private NetworkCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new CacheRepo(new ArrayStore);

        $factory = Mockery::mock(CacheFactory::class);
        $factory->shouldReceive('store')->andReturn($this->store);

        $this->cache = new NetworkCache($factory, [
            'enabled' => true,
            'prefix' => 'naija_test',
            'ttl' => [
                'mnp' => 1800,
                'prefix' => 7200,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_cache_keys_are_cryptographic_hashes_not_raw_numbers(): void
    {
        $number = '+2348031234567';
        $key = $this->cache->buildKey($number, 'prefix');

        $this->assertStringNotContainsString('2348031234567', $key);
        $this->assertStringNotContainsString('08031234567', $key);
        $this->assertStringContainsString('naija_test:v1:prefix:', $key);
        $this->assertStringContainsString(hash('sha256', $number), $key);
    }

    public function test_remember_caches_successful_result(): void
    {
        $number = '+2348031234567';
        $callCount = 0;

        $callback = function () use (&$callCount, $number) {
            $callCount++;

            return new NetworkLookupResult(
                phoneNumber: $number,
                network: Network::MTN,
                originalNetwork: Network::MTN,
                status: LookupStatus::INFERRED
            );
        };

        // First call populates cache
        $result1 = $this->cache->remember($number, 'prefix', $callback);
        $this->assertSame(1, $callCount);
        $this->assertSame(Network::MTN, $result1->network);

        // Second call should retrieve from cache
        $result2 = $this->cache->remember($number, 'prefix', $callback);
        $this->assertSame(1, $callCount); // Callback was not executed again
        $this->assertSame(Network::MTN, $result2->network);
    }

    public function test_it_does_not_cache_failure_results(): void
    {
        $number = '+2348031234567';
        $callCount = 0;

        $callback = function () use (&$callCount, $number) {
            $callCount++;

            return new NetworkLookupResult(
                phoneNumber: $number,
                network: Network::UNKNOWN,
                status: LookupStatus::PROVIDER_UNAVAILABLE
            );
        };

        $this->cache->remember($number, 'mnp', $callback);
        $this->assertSame(1, $callCount);

        // Second call must call callback again because failure was not cached
        $this->cache->remember($number, 'mnp', $callback);
        $this->assertSame(2, $callCount);
    }

    public function test_forget_clears_cached_number(): void
    {
        $number = '+2348031234567';
        $callCount = 0;

        $callback = fn () => new NetworkLookupResult(
            phoneNumber: $number,
            network: Network::MTN,
            status: LookupStatus::INFERRED
        );

        $this->cache->remember($number, 'prefix', $callback);
        $this->cache->forget($number);

        // After forget, cache key does not exist
        $key = $this->cache->buildKey($number, 'prefix');
        $this->assertNull($this->store->get($key));
    }

    public function test_flush_all_increments_cache_version(): void
    {
        $number = '+2348031234567';
        $callCount = 0;

        $callback = function () use (&$callCount, $number) {
            $callCount++;

            return new NetworkLookupResult(
                phoneNumber: $number,
                network: Network::MTN,
                status: LookupStatus::INFERRED
            );
        };

        $this->cache->remember($number, 'prefix', $callback);
        $this->assertSame(1, $callCount);

        // Flush all cache
        $this->cache->flushAll();

        // Following lookup will compute new version key and call callback
        $this->cache->remember($number, 'prefix', $callback);
        $this->assertSame(2, $callCount);
    }
}
