# NaijaTel

> **Nigerian Telecom Intelligence for Laravel**
> *Know the Network. Power the Connection.*

[![Latest Version on Packagist](https://img.shields.io/packagist/v/timbaron/naija-tel.svg?style=flat-square)](https://packagist.org/packages/timbaron/naija-tel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/timbaron/naija-tel/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/timbaron/naija-tel/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/timbaron/naija-tel.svg?style=flat-square)](https://packagist.org/packages/timbaron/naija-tel)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

NaijaTel is a telecommunications intelligence package for Laravel applications operating in Nigeria. It delivers phone number normalization, numbering-plan validation, offline original telecom network detection, and Mobile Number Portability (MNP) verification.

---

## Features

- **Offline Prefix Detection**: Invalidate and map Nigerian mobile prefixes without external API costs or latency.
- **Mobile Number Portability (MNP)**: Verified live network routing via clearinghouse adapters.
- **Porting Detection**: Distinguishes original allocated donor networks from active serving networks.
- **Strict Normalization**: Cleans and canonicalizes phone numbers into E.164 (`+234...`).
- **NCC Plan Compliance**: Validates 10-digit national significant numbers across 4-digit and 5-digit prefixes.
- **Privacy First (PII Safe)**: Automatically masks phone numbers in logs and generates deterministic SHA-256 cache keys.
- **High-Performance Caching**: Multi-tier caching with discrete TTLs for prefix inference and MNP lookups.
- **Extensible Architecture**: Register custom telecom aggregators or VAS providers via `NaijaTel::extend()`.
- **Developer Experience**: Built-in test simulation harness (`NaijaTel::fake()`), strict typing, and zero database dependencies.

---

## Requirements

- **PHP**: `^8.2` (including PHP 8.3 and 8.4)
- **Laravel**: `^11.0` or `^12.0`
- **Composer**: `v2`

---

## Installation

Install the package into your Laravel application via Composer:

```bash
composer require timbaron/naija-tel
```

The package automatically registers its service provider and facade via Laravel package discovery.

### Publish Configuration

Publish the configuration file to `config/naija-tel.php`:

```bash
php artisan vendor:publish --tag=naija-tel-config
```

Optional: You may also publish the prefix dataset if you need to maintain localized prefix overrides:

```bash
php artisan vendor:publish --tag=naija-tel-data
```

---

## Configuration

The default configuration file is located at `config/naija-tel.php`:

```php
return [
    'default_country' => env('NAIJATEL_DEFAULT_COUNTRY', 'NG'),

    'default_provider' => env('NAIJATEL_DEFAULT_PROVIDER', 'fake'),

    'providers' => [
        'icn' => [
            'enabled' => env('NAIJATEL_ICN_ENABLED', false),
            'base_url' => env('NAIJATEL_ICN_BASE_URL', 'https://api.interconnectnigeria.com/v1'),
            'api_key' => env('NAIJATEL_ICN_API_KEY'),
            'timeout' => (float) env('NAIJATEL_ICN_TIMEOUT', 10.0),
            'connect_timeout' => (float) env('NAIJATEL_ICN_CONNECT_TIMEOUT', 5.0),
        ],
    ],

    'cache' => [
        'enabled' => env('NAIJATEL_CACHE_ENABLED', true),
        'store' => env('NAIJATEL_CACHE_STORE'),
        'prefix' => env('NAIJATEL_CACHE_PREFIX', 'naija_tel'),
        'ttl' => [
            'mnp' => (int) env('NAIJATEL_CACHE_TTL_MNP', 3600),
            'prefix' => (int) env('NAIJATEL_CACHE_TTL_PREFIX', 86400),
        ],
    ],

    'fallback' => [
        'enabled' => env('NAIJATEL_FALLBACK_ENABLED', true),
        'provider' => 'prefix',
    ],

    'logging' => [
        'enabled' => env('NAIJATEL_LOGGING_ENABLED', true),
        'channel' => env('NAIJATEL_LOG_CHANNEL'),
        'mask_phone_numbers' => env('NAIJATEL_LOG_MASK_NUMBERS', true),
    ],
];
```

---

## Quick Start & Usage

### 1. Phone Number Normalization

NaijaTel handles various Nigerian phone number formats:

```php
use NaijaTel\Facades\NaijaTel;

// All format variations normalize to canonical E.164
NaijaTel::normalize('08031234567');       // "+2348031234567"
NaijaTel::normalize('+2348031234567');    // "+2348031234567"
NaijaTel::normalize('2348031234567');     // "+2348031234567"
NaijaTel::normalize('8031234567');        // "+2348031234567"
NaijaTel::normalize('0803 123 4567');     // "+2348031234567"
NaijaTel::normalize('0803-123-4567');     // "+2348031234567"
```

### 2. Validation & Parsing

Validate numbers or parse them into a strongly-typed `PhoneNumber` DTO:

```php
if (NaijaTel::isValid('08031234567')) {
    $phone = NaijaTel::parse('08031234567');

    $phone->normalized;                 // "+2348031234567"
    $phone->national;                   // "0803 123 4567"
    $phone->international;              // "+234 803 123 4567"
    $phone->countryCode;                // "+234"
    $phone->nationalSignificantNumber;  // "8031234567"
    $phone->countryIso;                 // "NG"
    $phone->masked;                     // "+234 803 *** *567"
}
```

### 3. Offline Prefix Detection (Free & Local)

Use `detect()` or `detectByPrefix()` for free, local detection based on NCC numbering plan allocations:

```php
$result = NaijaTel::detect('08031234567');

$result->network->value;     // "mtn"
$result->network->label();   // "MTN Nigeria"
$result->verified;           // false (Prefix matching is inferred, never verified)
$result->method->value;      // "prefix"
$result->isPorted;           // null (Prefix cannot infer porting status)
```

> **Important**: Prefix detection indicates the *donor network* originally assigned to the prefix. It never claims authoritative MNP verification.

### 4. Authoritative MNP Network Lookup

When your application requires verified current routing (e.g. for airtime or data VTU delivery), use `lookup()`:

```php
$result = NaijaTel::lookup('08031234567');

if ($result->verified) {
    echo "Serving Network: " . $result->network->label();
    echo "Original Network: " . $result->originalNetwork->label();
    echo "Has Ported: " . ($result->isPorted ? 'Yes' : 'No');
}
```

### 5. Portability Check

Check whether a subscriber has ported to a different operator:

```php
$isPorted = NaijaTel::isPorted('08031234567'); // bool|null
```

---

## Supported Nigerian Networks

NaijaTel represents networks using backed enums:

```php
use NaijaTel\Enums\Network;

Network::MTN;         // "mtn"       - MTN Nigeria
Network::AIRTEL;      // "airtel"    - Airtel Nigeria
Network::GLO;         // "glo"       - Globacom (Glo)
Network::NINE_MOBILE; // "9mobile"   - 9mobile
Network::UNKNOWN;     // "unknown"   - Unknown Network
```

### Verified Prefix Dataset

NaijaTel maintains an allocation dataset aligned with the **Nigerian Communications Commission (NCC) National Numbering Plan**:

| Network | Allocated Prefixes |
|---|---|
| **MTN Nigeria** | `0803`, `0806`, `0703`, `0706`, `0813`, `0816`, `0810`, `0814`, `0903`, `0906`, `0913`, `0916`, `0704`, `0707`, `07025`, `07026` |
| **Airtel Nigeria** | `0802`, `0808`, `0708`, `0812`, `0701`, `0902`, `0901`, `0904`, `0907`, `0911`, `0912` |
| **Globacom (Glo)** | `0805`, `0807`, `0705`, `0815`, `0811`, `0905`, `0915` |
| **9mobile** | `0809`, `0817`, `0818`, `0908`, `0909` |

Longest-prefix matching is utilized automatically to resolve 5-digit ranges (e.g. `07025` and `07026` for legacy Visafone/MTN).

---

## MNP Provider Architecture

### Extending with Custom Providers

You can register custom MNP lookup providers (e.g. your VTU provider, SMS gateway, or custom clearinghouse API) using `NaijaTel::extend()` in a service provider:

```php
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Data\NetworkLookupResult;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;
use NaijaTel\Facades\NaijaTel;

NaijaTel::extend('my-vtu-provider', function ($app) {
    return new class implements MnpProviderInterface {
        public function getName(): string
        {
            return 'my-vtu-provider';
        }

        public function lookup(string $phoneNumber): NetworkLookupResult
        {
            // Call external API
            // ...
            return new NetworkLookupResult(
                phoneNumber: $phoneNumber,
                network: Network::AIRTEL,
                originalNetwork: Network::MTN,
                isPorted: true,
                verified: true,
                method: DetectionMethod::CUSTOM,
                status: LookupStatus::VERIFIED,
                provider: $this->getName()
            );
        }
    };
});
```

To use a specific provider for a single call:

```php
$result = NaijaTel::using('my-vtu-provider')->lookup('08031234567');
```

### Interconnect Clearinghouse Nigeria (ICN)

ICN (Interconnect Clearinghouse Nigeria Limited) is the designated clearinghouse for Mobile Number Portability in Nigeria.

- **Status**: Adapter structure included (`IcnMnpProvider`).
- **Prerequisites**: Production integration requires an executed commercial SLA with ICN and IP whitelisting.
- **Documentation**: [https://interconnectnigeria.com](https://interconnectnigeria.com)

---

## Caching

To optimize performance and eliminate repetitive external API costs:
- Prefix detection is cached for 24 hours by default.
- MNP lookup results are cached for 1 hour by default.
- Cache keys use deterministic cryptographic SHA-256 hashes of normalized phone numbers to protect subscriber privacy in storage.

### Cache Management

```php
// Clear cache for a specific number
NaijaTel::clearCache('08031234567');

// Invalidate all NaijaTel cached lookups without flushing other application keys
NaijaTel::clearAllCache();
```

---

## Testing & Simulation

NaijaTel includes a test double harness inspired by Laravel's native fakes:

```php
use NaijaTel\Enums\Network;
use NaijaTel\Facades\NaijaTel;

// Simulate ported numbers in unit or feature tests
NaijaTel::fake([
    '08031234567' => [
        'network' => Network::AIRTEL,
        'original_network' => Network::MTN,
    ],
]);

$result = NaijaTel::lookup('08031234567');

$this->assertTrue($result->isPorted);
$this->assertSame(Network::AIRTEL, $result->network);
$this->assertSame(Network::MTN, $result->originalNetwork);
```

Run package tests:

```bash
composer test
```

---

## Security & Privacy

- **Masked Numbers**: Phone numbers in log files and exception traces are masked (e.g. `+234 803 *** *567`).
- **Encrypted Transports**: All provider requests require HTTPS.
- **No PII Leaks**: Deterministic hashing prevents raw phone numbers from appearing in cache keys.

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
