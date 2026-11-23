<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Integration;

use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Facades\NaijaTel;
use NaijaTel\Tests\TestCase;

class PortingDetectionTest extends TestCase
{
    public function test_critical_porting_scenario_original_mtn_ported_to_airtel(): void
    {
        // 0803 is allocated to MTN
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
        $this->assertSame(DetectionMethod::MNP, $result->method);
        $this->assertSame(LookupStatus::VERIFIED, $result->status);
        $this->assertTrue(NaijaTel::isPorted('08031234567'));
    }

    public function test_critical_porting_scenario_original_airtel_ported_to_mtn(): void
    {
        // 0802 is allocated to Airtel
        NaijaTel::fake([
            '08021234567' => [
                'network' => Network::MTN,
                'original_network' => Network::AIRTEL,
            ],
        ]);

        $result = NaijaTel::lookup('08021234567');

        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::AIRTEL, $result->originalNetwork);
        $this->assertTrue($result->isPorted);
        $this->assertTrue($result->verified);
        $this->assertTrue(NaijaTel::isPorted('08021234567'));
    }

    public function test_critical_porting_scenario_original_glo_ported_to_9mobile(): void
    {
        // 0805 is allocated to Glo
        NaijaTel::fake([
            '08051234567' => [
                'network' => Network::NINE_MOBILE,
                'original_network' => Network::GLO,
            ],
        ]);

        $result = NaijaTel::lookup('08051234567');

        $this->assertSame(Network::NINE_MOBILE, $result->network);
        $this->assertSame(Network::GLO, $result->originalNetwork);
        $this->assertTrue($result->isPorted);
        $this->assertTrue($result->verified);
    }

    public function test_non_ported_number_scenario(): void
    {
        // 0803 remains on MTN
        NaijaTel::fake([
            '08031234567' => [
                'network' => Network::MTN,
                'original_network' => Network::MTN,
            ],
        ]);

        $result = NaijaTel::lookup('08031234567');

        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertFalse($result->isPorted);
        $this->assertTrue($result->verified);
        $this->assertFalse(NaijaTel::isPorted('08031234567'));
    }

    public function test_prefix_only_detection_never_claims_number_is_or_is_not_ported(): void
    {
        $result = NaijaTel::detect('08031234567');

        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertNull($result->isPorted); // MUST BE NULL
        $this->assertFalse($result->verified); // MUST BE FALSE
        $this->assertSame(DetectionMethod::PREFIX, $result->method);
    }
}
