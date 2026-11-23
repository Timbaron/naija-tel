<?php

declare(strict_types=1);

namespace NaijaTel\Facades;

use Illuminate\Support\Facades\Facade;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Data\PhoneNumber;
use NaijaTel\Enums\Network;
use NaijaTel\Providers\FakeMnpProvider;
use NaijaTel\Services\NetworkDetector;
use NaijaTel\Services\ProviderManager;

/**
 * @method static NetworkLookupResult detect(string $phoneNumber)
 * @method static NetworkLookupResult detectByPrefix(string $phoneNumber)
 * @method static NetworkLookupResult lookup(string $phoneNumber, ?string $provider = null)
 * @method static bool|null isPorted(string $phoneNumber, ?string $provider = null)
 * @method static string normalize(string $phoneNumber)
 * @method static PhoneNumber parse(string $phoneNumber)
 * @method static bool isValid(string $phoneNumber)
 * @method static void validate(string $phoneNumber)
 * @method static string formatNational(string $phoneNumber)
 * @method static string formatInternational(string $phoneNumber)
 * @method static string|null getCountryCode(string $phoneNumber)
 * @method static array<int, array{id: string, name: string}> getSupportedNetworks()
 * @method static bool clearCache(string $phoneNumber)
 * @method static bool clearAllCache()
 * @method static NetworkDetector extend(string $name, \Closure|string $creator)
 * @method static NetworkDetector using(string $provider)
 *
 * @see NetworkDetector
 */
class NaijaTel extends Facade
{
    /**
     * Replace the bound instance with a preconfigured FakeMnpProvider for testing.
     *
     * @param  array<string, array{network: Network|string, original_network?: Network|string|null}>  $simulations
     */
    public static function fake(array $simulations = []): FakeMnpProvider
    {
        $app = static::getFacadeApplication();
        if ($app === null) {
            throw new \RuntimeException('Application container not set on Facade.');
        }

        /** @var FakeMnpProvider $fakeProvider */
        $fakeProvider = $app->make(FakeMnpProvider::class);

        foreach ($simulations as $number => $config) {
            $currentNet = is_string($config['network'])
                ? Network::tryFromString($config['network'])
                : $config['network'];

            $origNet = isset($config['original_network'])
                ? (is_string($config['original_network']) ? Network::tryFromString($config['original_network']) : $config['original_network'])
                : null;

            $fakeProvider->setPortedNumber($number, $currentNet, $origNet);
        }

        /** @var ProviderManager $providerManager */
        $providerManager = $app->make(ProviderManager::class);
        $providerManager->extend('fake', fn () => $fakeProvider);

        // Configure default provider to fake
        $app->make('config')->set('naija-tel.default_provider', 'fake');

        return $fakeProvider;
    }

    protected static function getFacadeAccessor(): string
    {
        return 'naija-tel';
    }
}
