<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Feature;

use NaijaTel\NaijaTelServiceProvider;
use NaijaTel\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    public function test_configuration_publish_tags_exist(): void
    {
        $publishGroups = NaijaTelServiceProvider::$publishGroups;

        $this->assertArrayHasKey('naija-tel-config', $publishGroups);
        $this->assertArrayHasKey('naija-tel-data', $publishGroups);
    }

    public function test_custom_cache_ttl_settings(): void
    {
        $this->app['config']->set('naija-tel.cache.ttl.mnp', 7200);
        $this->app['config']->set('naija-tel.cache.ttl.prefix', 172800);

        $this->assertSame(7200, $this->app['config']->get('naija-tel.cache.ttl.mnp'));
        $this->assertSame(172800, $this->app['config']->get('naija-tel.cache.ttl.prefix'));
    }
}
