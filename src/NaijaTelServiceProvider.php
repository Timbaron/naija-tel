<?php

declare(strict_types=1);

namespace NaijaTel;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use NaijaTel\Contracts\NetworkDetectorInterface;
use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Providers\FakeMnpProvider;
use NaijaTel\Services\MnpLookupService;
use NaijaTel\Services\NetworkCache;
use NaijaTel\Services\NetworkDetector;
use NaijaTel\Services\PhoneNumberNormalizer;
use NaijaTel\Services\PhoneNumberValidator;
use NaijaTel\Services\PrefixDetector;
use NaijaTel\Services\ProviderManager;
use NaijaTel\Support\NetworkPrefixRepository;
use Psr\Log\LoggerInterface;

class NaijaTelServiceProvider extends ServiceProvider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/naija-tel.php', 'naija-tel');

        $this->app->singleton(NetworkPrefixRepository::class, function () {
            return new NetworkPrefixRepository;
        });

        $this->app->singleton(PhoneNumberValidator::class, function (Container $app) {
            return new PhoneNumberValidator(
                $app->make(NetworkPrefixRepository::class)
            );
        });

        $this->app->singleton(PhoneNumberNormalizer::class, function (Container $app) {
            return new PhoneNumberNormalizer(
                $app->make(PhoneNumberValidator::class)
            );
        });

        $this->app->bind(PhoneNumberParserInterface::class, PhoneNumberNormalizer::class);

        $this->app->singleton(PrefixDetector::class, function (Container $app) {
            return new PrefixDetector(
                $app->make(NetworkPrefixRepository::class),
                $app->make(PhoneNumberParserInterface::class)
            );
        });

        $this->app->singleton(NetworkCache::class, function (Container $app) {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);

            return new NetworkCache(
                $app->make(CacheFactory::class),
                (array) $config->get('naija-tel.cache', [])
            );
        });

        $this->app->singleton(ProviderManager::class, function (Container $app) {
            return new ProviderManager($app);
        });

        $this->app->singleton(FakeMnpProvider::class, function (Container $app) {
            return new FakeMnpProvider(
                $app->make(NetworkPrefixRepository::class),
                $app->make(PhoneNumberParserInterface::class)
            );
        });

        $this->app->singleton(MnpLookupService::class, function (Container $app) {
            /** @var ConfigRepository $config */
            $config = $app->make(ConfigRepository::class);
            $logChannel = $config->get('naija-tel.logging.channel');

            $logger = $app->bound(LoggerInterface::class)
                ? ($logChannel ? $app->make('log')->channel($logChannel) : $app->make(LoggerInterface::class))
                : null;

            return new MnpLookupService(
                providerManager: $app->make(ProviderManager::class),
                prefixDetector: $app->make(PrefixDetector::class),
                cache: $app->make(NetworkCache::class),
                normalizer: $app->make(PhoneNumberParserInterface::class),
                config: $config,
                logger: $logger
            );
        });

        $this->app->singleton(NetworkDetector::class, function (Container $app) {
            return new NetworkDetector(
                normalizer: $app->make(PhoneNumberParserInterface::class),
                prefixDetector: $app->make(PrefixDetector::class),
                mnpLookupService: $app->make(MnpLookupService::class),
                cache: $app->make(NetworkCache::class),
                providerManager: $app->make(ProviderManager::class)
            );
        });

        $this->app->bind(NetworkDetectorInterface::class, NetworkDetector::class);

        $this->app->alias(NetworkDetector::class, 'naija-tel');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/naija-tel.php' => $this->app->configPath('naija-tel.php'),
            ], 'naija-tel-config');

            $this->publishes([
                __DIR__.'/../resources/data/nigeria-prefixes.php' => $this->app->resourcePath('naija-tel/nigeria-prefixes.php'),
            ], 'naija-tel-data');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            NetworkPrefixRepository::class,
            PhoneNumberValidator::class,
            PhoneNumberNormalizer::class,
            PhoneNumberParserInterface::class,
            PrefixDetector::class,
            NetworkCache::class,
            ProviderManager::class,
            FakeMnpProvider::class,
            MnpLookupService::class,
            NetworkDetector::class,
            NetworkDetectorInterface::class,
            'naija-tel',
        ];
    }
}
