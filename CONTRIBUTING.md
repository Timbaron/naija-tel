# Contributing to NaijaTel

Thank you for contributing to NaijaTel!

## Development Guidelines

1. **Coding Style**: NaijaTel follows PSR-12 and Laravel Pint style.
   ```bash
   ./vendor/bin/pint --test
   ```

2. **Static Analysis**: All code must pass PHPStan Level 8 without warnings.
   ```bash
   ./vendor/bin/phpstan analyse
   ```

3. **Automated Tests**: Maintain unit and feature tests with 100% test passing rate.
   ```bash
   ./vendor/bin/phpunit
   ```

4. **Prefix Allocations**: Any updates to mobile prefix allocations in `resources/data/nigeria-prefixes.php` must include official citations from the Nigerian Communications Commission (NCC).

5. **Privacy First**: Phone numbers are personally identifiable information (PII). Never include real phone numbers in test fixtures or logs.
