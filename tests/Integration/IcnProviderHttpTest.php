<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Integration;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Exceptions\ConfigurationException;
use NaijaTel\Exceptions\ProviderException;
use NaijaTel\Exceptions\ProviderUnavailableException;
use NaijaTel\Providers\Icn\IcnMnpProvider;
use NaijaTel\Support\NetworkPrefixRepository;
use NaijaTel\Tests\TestCase;

class IcnProviderHttpTest extends TestCase
{
    private IcnMnpProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new IcnMnpProvider(
            http: $this->app->make(Factory::class),
            prefixRepository: $this->app->make(NetworkPrefixRepository::class),
            normalizer: $this->app->make(PhoneNumberParserInterface::class),
            config: [
                'enabled' => true,
                'base_url' => 'https://api.icn-lookup.example.com',
                'api_key' => 'test-api-key',
                'timeout' => 5.0,
                'connect_timeout' => 2.0,
            ]
        );
    }

    public function test_successful_icn_lookup_for_ported_number(): void
    {
        // 0803 is originally allocated to MTN, but ICN reports it is now on Airtel
        Http::fake([
            'https://api.icn-lookup.example.com/lookup' => Http::response([
                'status' => 'success',
                'phone_number' => '+2348031234567',
                'network' => 'airtel',
                'is_ported' => true,
            ], 200),
        ]);

        $result = $this->provider->lookup('08031234567');

        $this->assertInstanceOf(NetworkLookupResult::class, $result);
        $this->assertSame(Network::AIRTEL, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertTrue($result->isPorted);
        $this->assertTrue($result->verified);
        $this->assertSame(DetectionMethod::MNP, $result->method);
        $this->assertSame(LookupStatus::VERIFIED, $result->status);
        $this->assertSame('icn', $result->provider);
    }

    public function test_successful_icn_lookup_for_non_ported_number(): void
    {
        Http::fake([
            'https://api.icn-lookup.example.com/lookup' => Http::response([
                'status' => 'success',
                'phone_number' => '+2348031234567',
                'network' => 'mtn',
                'is_ported' => false,
            ], 200),
        ]);

        $result = $this->provider->lookup('08031234567');

        $this->assertSame(Network::MTN, $result->network);
        $this->assertSame(Network::MTN, $result->originalNetwork);
        $this->assertFalse($result->isPorted);
        $this->assertTrue($result->verified);
    }

    public function test_icn_auth_failure_throws_provider_exception(): void
    {
        Http::fake([
            'https://api.icn-lookup.example.com/lookup' => Http::response([
                'error' => 'Unauthorized access token',
            ], 401),
        ]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Authentication failed with ICN Number Lookup API');

        $this->provider->lookup('08031234567');
    }

    public function test_icn_rate_limiting_throws_provider_exception(): void
    {
        Http::fake([
            'https://api.icn-lookup.example.com/lookup' => Http::response([
                'error' => 'Too many requests',
            ], 429),
        ]);

        $this->expectException(ProviderException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->provider->lookup('08031234567');
    }

    public function test_icn_connection_timeout_throws_provider_unavailable_exception(): void
    {
        Http::fake([
            'https://api.icn-lookup.example.com/lookup' => function () {
                throw new ConnectionException('Connection timed out');
            },
        ]);

        $this->expectException(ProviderUnavailableException::class);
        $this->provider->lookup('08031234567');
    }

    public function test_disabled_icn_provider_throws_configuration_exception(): void
    {
        $disabledProvider = new IcnMnpProvider(
            http: $this->app->make(Factory::class),
            prefixRepository: $this->app->make(NetworkPrefixRepository::class),
            normalizer: $this->app->make(PhoneNumberParserInterface::class),
            config: [
                'enabled' => false,
            ]
        );

        $this->expectException(ConfigurationException::class);
        $disabledProvider->lookup('08031234567');
    }
}
