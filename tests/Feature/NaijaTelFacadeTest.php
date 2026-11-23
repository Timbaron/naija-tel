<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Feature;

use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Data\PhoneNumber;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\Network;
use NaijaTel\Facades\NaijaTel;
use NaijaTel\Tests\TestCase;

class NaijaTelFacadeTest extends TestCase
{
    public function test_facade_resolves_and_detects_by_prefix(): void
    {
        $result = NaijaTel::detect('08031234567');

        $this->assertInstanceOf(NetworkLookupResult::class, $result);
        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertNull($result->isPorted);
        $this->assertFalse($result->verified);
        $this->assertSame(DetectionMethod::PREFIX, $result->method);
    }

    public function test_facade_detect_by_prefix_explicit_method(): void
    {
        $result = NaijaTel::detectByPrefix('08021234567');

        $this->assertSame(Network::AIRTEL, $result->network);
        $this->assertFalse($result->verified);
    }

    public function test_facade_normalizes_numbers(): void
    {
        $this->assertSame('+2348031234567', NaijaTel::normalize('08031234567'));
        $this->assertSame('+2348031234567', NaijaTel::normalize('8031234567'));
        $this->assertSame('+2348031234567', NaijaTel::normalize('+234 803 123 4567'));
    }

    public function test_facade_validates_numbers(): void
    {
        $this->assertTrue(NaijaTel::isValid('08031234567'));
        $this->assertFalse(NaijaTel::isValid('08001234567'));
        $this->assertFalse(NaijaTel::isValid('+12025550123'));
    }

    public function test_facade_parses_into_dto(): void
    {
        $phone = NaijaTel::parse('08031234567');

        $this->assertInstanceOf(PhoneNumber::class, $phone);
        $this->assertSame('+2348031234567', $phone->normalized);
        $this->assertSame('0803 123 4567', $phone->national);
        $this->assertSame('+234 803 123 4567', $phone->international);
    }

    public function test_facade_formatting_helpers(): void
    {
        $this->assertSame('0803 123 4567', NaijaTel::formatNational('8031234567'));
        $this->assertSame('+234 803 123 4567', NaijaTel::formatInternational('08031234567'));
        $this->assertSame('+234', NaijaTel::getCountryCode('08031234567'));
    }

    public function test_facade_returns_supported_networks(): void
    {
        $networks = NaijaTel::getSupportedNetworks();
        $this->assertCount(4, $networks);
        $ids = array_column($networks, 'id');
        $this->assertContains('mtn', $ids);
        $this->assertContains('airtel', $ids);
        $this->assertContains('glo', $ids);
        $this->assertContains('9mobile', $ids);
    }

    public function test_facade_cache_invalidation(): void
    {
        $this->assertTrue(NaijaTel::clearCache('08031234567'));
        $this->assertTrue(NaijaTel::clearAllCache());
    }

    public function test_facade_fake_sets_up_simulated_lookups(): void
    {
        NaijaTel::fake([
            '08031234567' => [
                'network' => Network::AIRTEL,
                'original_network' => Network::MTN,
            ],
        ]);

        $result = NaijaTel::lookup('08031234567');

        $this->assertSame(Network::AIRTEL, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertTrue($result->isPorted);
        $this->assertTrue($result->verified);
    }
}
