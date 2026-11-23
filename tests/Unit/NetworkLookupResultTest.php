<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use DateTimeImmutable;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use PHPUnit\Framework\TestCase;

class NetworkLookupResultTest extends TestCase
{
    public function test_it_creates_result_with_correct_properties(): void
    {
        $now = new DateTimeImmutable;
        $result = new NetworkLookupResult(
            phoneNumber: '+2348031234567',
            network: Network::AIRTEL,
            originalNetwork: Network::MTN,
            isPorted: true,
            verified: true,
            method: DetectionMethod::MNP,
            status: LookupStatus::VERIFIED,
            provider: 'icn',
            lookupAt: $now,
            metadata: ['test' => true]
        );

        $this->assertSame('+2348031234567', $result->phoneNumber);
        $this->assertSame(Network::AIRTEL, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertTrue($result->isPorted());
        $this->assertTrue($result->isVerified());
        $this->assertTrue($result->isSuccess());
        $this->assertSame(DetectionMethod::MNP, $result->method);
        $this->assertSame(LookupStatus::VERIFIED, $result->status);
        $this->assertSame('icn', $result->provider);

        $array = $result->toArray();
        $this->assertSame('+2348031234567', $array['phone_number']);
        $this->assertSame('airtel', $array['network']);
        $this->assertSame('Airtel Nigeria', $array['network_label']);
        $this->assertSame('mtn', $array['original_network']);
        $this->assertSame('MTN Nigeria', $array['original_network_label']);
        $this->assertTrue($array['is_ported']);
        $this->assertTrue($array['verified']);
        $this->assertSame('mnp', $array['method']);
        $this->assertSame('verified', $array['status']);

        $json = json_encode($result);
        $this->assertIsString($json);
        $this->assertStringContainsString('"network":"airtel"', $json);
    }

    public function test_is_success_returns_false_for_unknown_network(): void
    {
        $result = new NetworkLookupResult(
            phoneNumber: '+2348031234567',
            network: Network::UNKNOWN,
            status: LookupStatus::UNKNOWN
        );

        $this->assertFalse($result->isSuccess());
    }

    public function test_is_success_returns_false_for_failure_status(): void
    {
        $result = new NetworkLookupResult(
            phoneNumber: '+2348031234567',
            network: Network::MTN,
            status: LookupStatus::PROVIDER_UNAVAILABLE
        );

        $this->assertFalse($result->isSuccess());
    }
}
