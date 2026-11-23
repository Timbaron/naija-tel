<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Integration;

use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Exceptions\ProviderUnavailableException;
use NaijaTel\Facades\NaijaTel;
use NaijaTel\Tests\TestCase;

class FallbackBehaviorTest extends TestCase
{
    public function test_when_fallback_enabled_provider_failure_returns_unverified_prefix_result(): void
    {
        $this->app['config']->set('naija-tel.fallback.enabled', true);

        $fake = NaijaTel::fake();
        $fake->simulateTimeout(true);

        $result = NaijaTel::lookup('08031234567');

        // Fallback should infer network from prefix (0803 -> MTN)
        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);

        // Crucial: Must NOT be verified, must NOT claim porting status, status must reflect failure
        $this->assertFalse($result->verified);
        $this->assertNull($result->isPorted);
        $this->assertSame(LookupStatus::PROVIDER_UNAVAILABLE, $result->status);
        $this->assertSame(DetectionMethod::PREFIX, $result->method);
        $this->assertTrue($result->metadata['fallback_used']);
    }

    public function test_when_fallback_disabled_provider_failure_throws_exception(): void
    {
        $this->app['config']->set('naija-tel.fallback.enabled', false);

        $fake = NaijaTel::fake();
        $fake->simulateTimeout(true);

        $this->expectException(ProviderUnavailableException::class);
        NaijaTel::lookup('08031234567');
    }
}
