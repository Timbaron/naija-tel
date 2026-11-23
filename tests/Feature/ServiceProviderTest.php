<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Feature;

use NaijaTel\Contracts\NetworkDetectorInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\NaijaTelServiceProvider;
use NaijaTel\Services\NetworkCache;
use NaijaTel\Services\NetworkDetector;
use NaijaTel\Services\PhoneNumberNormalizer;
use NaijaTel\Services\PhoneNumberValidator;
use NaijaTel\Services\PrefixDetector;
use NaijaTel\Services\ProviderManager;
use NaijaTel\Support\NetworkPrefixRepository;
use NaijaTel\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_it_registers_all_core_singletons(): void
    {
        $this->assertInstanceOf(NetworkPrefixRepository::class, $this->app->make(NetworkPrefixRepository::class));
        $this->assertInstanceOf(PhoneNumberValidator::class, $this->app->make(PhoneNumberValidator::class));
        $this->assertInstanceOf(PhoneNumberNormalizer::class, $this->app->make(PhoneNumberNormalizer::class));
        $this->assertInstanceOf(PhoneNumberParserInterface::class, $this->app->make(PhoneNumberParserInterface::class));
        $this->assertInstanceOf(PrefixDetector::class, $this->app->make(PrefixDetector::class));
        $this->assertInstanceOf(NetworkCache::class, $this->app->make(NetworkCache::class));
        $this->assertInstanceOf(ProviderManager::class, $this->app->make(ProviderManager::class));
        $this->assertInstanceOf(NetworkDetector::class, $this->app->make(NetworkDetector::class));
        $this->assertInstanceOf(NetworkDetectorInterface::class, $this->app->make(NetworkDetectorInterface::class));
        $this->assertInstanceOf(NetworkDetector::class, $this->app->make('naija-tel'));
    }

    public function test_it_merges_package_configuration(): void
    {
        $this->assertTrue($this->app['config']->has('naija-tel'));
        $this->assertSame('NG', $this->app['config']->get('naija-tel.default_country'));
        $this->assertTrue($this->app['config']->get('naija-tel.cache.enabled'));
    }

    public function test_provides_returns_expected_classes(): void
    {
        $provider = new NaijaTelServiceProvider($this->app);
        $provided = $provider->provides();

        $this->assertContains(NetworkDetector::class, $provided);
        $this->assertContains(NetworkDetectorInterface::class, $provided);
        $this->assertContains(PhoneNumberParserInterface::class, $provided);
        $this->assertContains('naija-tel', $provided);
    }
}
