<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use PHPUnit\Framework\TestCase;

class NetworkEnumTest extends TestCase
{
    public function test_it_has_expected_enum_values(): void
    {
        $this->assertSame('mtn', Network::MTN->value);
        $this->assertSame('airtel', Network::AIRTEL->value);
        $this->assertSame('glo', Network::GLO->value);
        $this->assertSame('9mobile', Network::NINE_MOBILE->value);
        $this->assertSame('unknown', Network::UNKNOWN->value);
    }

    public function test_it_provides_human_readable_labels(): void
    {
        $this->assertSame('MTN Nigeria', Network::MTN->label());
        $this->assertSame('Airtel Nigeria', Network::AIRTEL->label());
        $this->assertSame('Globacom (Glo)', Network::GLO->label());
        $this->assertSame('9mobile', Network::NINE_MOBILE->label());
        $this->assertSame('Unknown Network', Network::UNKNOWN->label());
    }

    public function test_is_known_returns_correct_boolean(): void
    {
        $this->assertTrue(Network::MTN->isKnown());
        $this->assertTrue(Network::AIRTEL->isKnown());
        $this->assertTrue(Network::GLO->isKnown());
        $this->assertTrue(Network::NINE_MOBILE->isKnown());
        $this->assertFalse(Network::UNKNOWN->isKnown());
    }

    public function test_supported_values_returns_four_major_networks(): void
    {
        $values = Network::supportedValues();
        $this->assertCount(4, $values);
        $this->assertContains('mtn', $values);
        $this->assertContains('airtel', $values);
        $this->assertContains('glo', $values);
        $this->assertContains('9mobile', $values);
        $this->assertNotContains('unknown', $values);
    }

    public function test_try_from_string_resolves_aliases_and_case_variations(): void
    {
        $this->assertSame(Network::MTN, Network::tryFromString('MTN'));
        $this->assertSame(Network::MTN, Network::tryFromString('mtn nigeria'));
        $this->assertSame(Network::AIRTEL, Network::tryFromString('Airtel NG'));
        $this->assertSame(Network::AIRTEL, Network::tryFromString('celtel'));
        $this->assertSame(Network::AIRTEL, Network::tryFromString('vmobile'));
        $this->assertSame(Network::GLO, Network::tryFromString('Globacom'));
        $this->assertSame(Network::NINE_MOBILE, Network::tryFromString('Etisalat'));
        $this->assertSame(Network::NINE_MOBILE, Network::tryFromString('9mobile'));
        $this->assertSame(Network::UNKNOWN, Network::tryFromString('vodacom'));
        $this->assertSame(Network::UNKNOWN, Network::tryFromString(null));
        $this->assertSame(Network::UNKNOWN, Network::tryFromString('   '));
    }

    public function test_detection_method_authoritative_check(): void
    {
        $this->assertFalse(DetectionMethod::PREFIX->isAuthoritative());
        $this->assertFalse(DetectionMethod::UNKNOWN->isAuthoritative());
        $this->assertTrue(DetectionMethod::MNP->isAuthoritative());
        $this->assertTrue(DetectionMethod::CUSTOM->isAuthoritative());
    }

    public function test_lookup_status_helper_methods(): void
    {
        $this->assertTrue(LookupStatus::VERIFIED->isVerified());
        $this->assertFalse(LookupStatus::INFERRED->isVerified());
        $this->assertFalse(LookupStatus::UNKNOWN->isVerified());

        $this->assertTrue(LookupStatus::PROVIDER_UNAVAILABLE->isFailure());
        $this->assertTrue(LookupStatus::INVALID->isFailure());
        $this->assertFalse(LookupStatus::VERIFIED->isFailure());
        $this->assertFalse(LookupStatus::INFERRED->isFailure());
    }
}
