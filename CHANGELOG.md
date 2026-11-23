# Changelog

All notable changes to `timbaron/naija-tel` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2026-11-23

### Added
- Phone number normalization to E.164 standard (`+234...`).
- Phone number validation adhering to Nigerian Communications Commission (NCC) numbering plan.
- Offline, local telecom prefix detection engine covering MTN, Airtel, Glo, and 9mobile.
- Mobile Number Portability (MNP) provider architecture and contracts.
- High-performance caching layer with deterministic SHA-256 keys to protect subscriber privacy.
- ProviderManager supporting custom MNP provider registration via `NaijaTel::extend()`.
- Simulation test provider (`FakeMnpProvider` & `NaijaTel::fake()`).
- Adapter structure for Interconnect Clearinghouse Nigeria (ICN) lookup service.
- Fallback resilience: configurable fallback to prefix inference on external provider failure.
- Masked logging for PII protection.
- Support for Laravel 11 and Laravel 12 on PHP 8.2, 8.3, and 8.4.
