<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Services\PhoneNumberNormalizer;
use NaijaTel\Services\PhoneNumberValidator;
use NaijaTel\Services\PrefixDetector;
use NaijaTel\Support\NetworkPrefixRepository;
use PHPUnit\Framework\TestCase;

class PrefixDetectorTest extends TestCase
{
    private PrefixDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $repo = new NetworkPrefixRepository;
        $normalizer = new PhoneNumberNormalizer(new PhoneNumberValidator($repo));
        $this->detector = new PrefixDetector($repo, $normalizer);
    }

    public function test_detects_mtn_number_by_prefix(): void
    {
        $result = $this->detector->detect('08031234567');

        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertNull($result->isPorted); // Must NEVER infer porting status
        $this->assertFalse($result->verified); // Must NEVER claim verification
        $this->assertSame(DetectionMethod::PREFIX, $result->method);
        $this->assertSame(LookupStatus::INFERRED, $result->status);
        $this->assertSame('+2348031234567', $result->phoneNumber);
    }

    public function test_detects_airtel_number_by_prefix(): void
    {
        $result = $this->detector->detect('08021234567');

        $this->assertSame(Network::AIRTEL, $result->network);
        $this->assertSame(Network::AIRTEL, $result->originalNetwork);
        $this->assertNull($result->isPorted);
        $this->assertFalse($result->verified);
        $this->assertSame(DetectionMethod::PREFIX, $result->method);
    }

    public function test_detects_glo_number_by_prefix(): void
    {
        $result = $this->detector->detect('08051234567');

        $this->assertSame(Network::GLO, $result->network);
        $this->assertSame(Network::GLO, $result->originalNetwork);
        $this->assertNull($result->isPorted);
        $this->assertFalse($result->verified);
    }

    public function test_detects_9mobile_number_by_prefix(): void
    {
        $result = $this->detector->detect('08091234567');

        $this->assertSame(Network::NINE_MOBILE, $result->network);
        $this->assertSame(Network::NINE_MOBILE, $result->originalNetwork);
        $this->assertNull($result->isPorted);
        $this->assertFalse($result->verified);
    }

    public function test_detects_5_digit_prefix_number(): void
    {
        $result = $this->detector->detect('07025123456');

        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
    }
}
