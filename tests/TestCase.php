<?php

declare(strict_types=1);

namespace NaijaTel\Tests;

use NaijaTel\Facades\NaijaTel;
use NaijaTel\NaijaTelServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NaijaTelServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'NaijaTel' => NaijaTel::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('cache.stores.array', [
            'driver' => 'array',
            'serialize' => false,
        ]);

        $app['config']->set('naija-tel.default_provider', 'fake');
        $app['config']->set('naija-tel.cache.enabled', true);
        $app['config']->set('naija-tel.cache.store', 'array');
    }
}
