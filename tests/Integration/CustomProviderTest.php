<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Integration;

use DateTimeImmutable;
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Facades\NaijaTel;
use NaijaTel\Tests\TestCase;

class TestCustomProviderStub implements MnpProviderInterface
{
    public function getName(): string
    {
        return 'test-class-provider';
    }

    public function lookup(string $phoneNumber): NetworkLookupResult
    {
        return new NetworkLookupResult(
            phoneNumber: '+2348031234567',
            network: Network::GLO,
            originalNetwork: Network::MTN,
            isPorted: true,
            verified: true,
            method: DetectionMethod::CUSTOM,
            status: LookupStatus::VERIFIED,
            provider: $this->getName(),
            lookupAt: new DateTimeImmutable
        );
    }
}

class CustomProviderTest extends TestCase
{
    public function test_can_register_custom_provider_via_closure(): void
    {
        NaijaTel::extend('telecom-aggregator', function () {
            return new class implements MnpProviderInterface
            {
                public function getName(): string
                {
                    return 'telecom-aggregator';
                }

                public function lookup(string $phoneNumber): NetworkLookupResult
                {
                    return new NetworkLookupResult(
                        phoneNumber: '+2348031234567',
                        network: Network::AIRTEL,
                        originalNetwork: Network::MTN,
                        isPorted: true,
                        verified: true,
                        method: DetectionMethod::CUSTOM,
                        status: LookupStatus::VERIFIED,
                        provider: 'telecom-aggregator'
                    );
                }
            };
        });

        $result = NaijaTel::using('telecom-aggregator')->lookup('08031234567');

        $this->assertSame(Network::AIRTEL, $result->network);
        $this->assertSame('telecom-aggregator', $result->provider);
        $this->assertTrue($result->verified);
        $this->assertSame(DetectionMethod::CUSTOM, $result->method);
    }

    public function test_can_register_custom_provider_via_class_name(): void
    {
        NaijaTel::extend('test-class-provider', TestCustomProviderStub::class);

        $result = NaijaTel::lookup('08031234567', 'test-class-provider');

        $this->assertSame(Network::GLO, $result->network);
        $this->assertSame('test-class-provider', $result->provider);
        $this->assertTrue($result->isPorted);
    }
}
