<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use NaijaTel\Exceptions\InvalidPhoneNumberException;
use NaijaTel\Exceptions\UnsupportedCountryException;
use NaijaTel\Services\PhoneNumberValidator;
use NaijaTel\Support\NetworkPrefixRepository;
use PHPUnit\Framework\TestCase;

class PhoneNumberValidatorTest extends TestCase
{
    private PhoneNumberValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PhoneNumberValidator(new NetworkPrefixRepository);
    }

    public function test_it_validates_various_valid_nigerian_number_formats(): void
    {
        $validFormats = [
            '08031234567',
            '+2348031234567',
            '2348031234567',
            '8031234567',
            '0803 123 4567',
            '0803-123-4567',
            '+234 803 123 4567',
            '(0803) 123-4567',
            '07025123456', // 5-digit prefix
        ];

        foreach ($validFormats as $format) {
            $this->assertTrue($this->validator->isValid($format), "Failed asserting that [{$format}] is valid.");
            $this->validator->validate($format); // Should not throw
        }
    }

    public function test_it_rejects_empty_phone_number(): void
    {
        $this->assertFalse($this->validator->isValid(''));
        $this->assertFalse($this->validator->isValid('   '));

        $this->expectException(InvalidPhoneNumberException::class);
        $this->validator->validate('');
    }

    public function test_it_rejects_short_or_long_numbers(): void
    {
        $this->assertFalse($this->validator->isValid('0803123')); // too short
        $this->assertFalse($this->validator->isValid('0803123456789999')); // too long

        $this->expectException(InvalidPhoneNumberException::class);
        $this->validator->validate('0803123');
    }

    public function test_it_rejects_non_mobile_first_digits(): void
    {
        // 01 is landline, not mobile
        $this->assertFalse($this->validator->isValid('01234567890'));
        $this->assertFalse($this->validator->isValid('02012345678'));
    }

    public function test_it_rejects_unallocated_mobile_prefixes(): void
    {
        // 0800 is toll free, not allocated mobile operator prefix
        $this->assertFalse($this->validator->isValid('08001234567'));

        $this->expectException(InvalidPhoneNumberException::class);
        $this->validator->validate('08001234567');
    }

    public function test_it_rejects_non_nigerian_international_codes(): void
    {
        $this->assertFalse($this->validator->isValid('+12025550123'));
        $this->assertFalse($this->validator->isValid('+447911123456'));

        $this->expectException(UnsupportedCountryException::class);
        $this->validator->validate('+12025550123');
    }

    public function test_it_rejects_unsupported_default_country_parameter(): void
    {
        $this->expectException(UnsupportedCountryException::class);
        $this->validator->validate('08031234567', 'US');
    }
}
