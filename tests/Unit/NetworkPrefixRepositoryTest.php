<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use NaijaTel\Enums\Network;
use NaijaTel\Support\NetworkPrefixRepository;
use PHPUnit\Framework\TestCase;

class NetworkPrefixRepositoryTest extends TestCase
{
    private NetworkPrefixRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new NetworkPrefixRepository;
    }

    public function test_it_loads_prefix_metadata(): void
    {
        $meta = $this->repository->getMetadata();
        $this->assertArrayHasKey('source', $meta);
        $this->assertArrayHasKey('country', $meta);
        $this->assertSame('Nigeria', $meta['country']);
        $this->assertSame('+234', $meta['country_code']);
    }

    public function test_longest_prefix_matching(): void
    {
        // 5-digit prefix matches Visafone/MTN
        $this->assertSame(Network::MTN, $this->repository->match('07025123456'));
        $this->assertSame(Network::MTN, $this->repository->match('07026123456'));

        // 4-digit prefix
        $this->assertSame(Network::MTN, $this->repository->match('07041234567'));
        $this->assertSame(Network::MTN, $this->repository->match('08031234567'));
    }

    public function test_every_supported_prefix_resolves_to_valid_network(): void
    {
        $prefixes = $this->repository->all();
        $this->assertNotEmpty($prefixes);

        foreach ($prefixes as $prefix => $networkIdentifier) {
            $matched = $this->repository->match($prefix.'1234567');
            $expected = Network::tryFromString($networkIdentifier);

            $this->assertSame(
                $expected,
                $matched,
                "Prefix [{$prefix}] did not match expected network [{$networkIdentifier}]."
            );
        }
    }

    public function test_unknown_prefixes_return_unknown_network(): void
    {
        $this->assertSame(Network::UNKNOWN, $this->repository->match('08001234567'));
        $this->assertSame(Network::UNKNOWN, $this->repository->match('01234567890'));
        $this->assertSame(Network::UNKNOWN, $this->repository->match('99999999999'));
    }

    public function test_has_prefix(): void
    {
        $this->assertTrue($this->repository->hasPrefix('0803'));
        $this->assertTrue($this->repository->hasPrefix('0802'));
        $this->assertTrue($this->repository->hasPrefix('0805'));
        $this->assertTrue($this->repository->hasPrefix('0809'));
        $this->assertTrue($this->repository->hasPrefix('07025'));
        $this->assertTrue($this->repository->hasPrefix('+234803'));
        $this->assertTrue($this->repository->hasPrefix('234803'));
        $this->assertTrue($this->repository->hasPrefix('803'));

        $this->assertFalse($this->repository->hasPrefix('0800'));
        $this->assertFalse($this->repository->hasPrefix('0700'));
    }

    public function test_get_prefixes_for_network(): void
    {
        $mtnPrefixes = $this->repository->getPrefixesForNetwork(Network::MTN);
        $this->assertContains('0803', $mtnPrefixes);
        $this->assertContains('0806', $mtnPrefixes);
        $this->assertContains('0703', $mtnPrefixes);
        $this->assertContains('07025', $mtnPrefixes);

        $airtelPrefixes = $this->repository->getPrefixesForNetwork(Network::AIRTEL);
        $this->assertContains('0802', $airtelPrefixes);
        $this->assertContains('0808', $airtelPrefixes);
        $this->assertContains('0708', $airtelPrefixes);

        $gloPrefixes = $this->repository->getPrefixesForNetwork(Network::GLO);
        $this->assertContains('0805', $gloPrefixes);
        $this->assertContains('0807', $gloPrefixes);
        $this->assertContains('0705', $gloPrefixes);

        $etisalatPrefixes = $this->repository->getPrefixesForNetwork(Network::NINE_MOBILE);
        $this->assertContains('0809', $etisalatPrefixes);
        $this->assertContains('0818', $etisalatPrefixes);
        $this->assertContains('0909', $etisalatPrefixes);
    }
}
