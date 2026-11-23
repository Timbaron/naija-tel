<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Feature;

use NaijaTel\Contracts\NetworkDetectorInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\Network;
use NaijaTel\Services\NetworkDetector;
use NaijaTel\Tests\TestCase;

class AirtimeServiceStub
{
    public function __construct(
        public NetworkDetector $detector,
        public PhoneNumberParserInterface $parser
    ) {}

    public function getNetwork(string $phoneNumber): NetworkLookupResult
    {
        return $this->detector->detect($phoneNumber);
    }
}

class DependencyInjectionTest extends TestCase
{
    public function test_classes_can_be_injected_via_type_hints(): void
    {
        /** @var AirtimeServiceStub $service */
        $service = $this->app->make(AirtimeServiceStub::class);

        $this->assertInstanceOf(NetworkDetector::class, $service->detector);
        $this->assertInstanceOf(PhoneNumberParserInterface::class, $service->parser);

        $result = $service->getNetwork('08031234567');
        $this->assertSame(Network::MTN, $result->network);
    }

    public function test_interface_binding_resolves(): void
    {
        $detector = $this->app->make(NetworkDetectorInterface::class);
        $this->assertInstanceOf(NetworkDetector::class, $detector);

        $result = $detector->detect('08021234567');
        $this->assertSame(Network::AIRTEL, $result->network);
    }
}
