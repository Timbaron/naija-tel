<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use NaijaTel\Data\PhoneNumber;
use NaijaTel\Exceptions\InvalidPhoneNumberException;
use NaijaTel\Services\PhoneNumberNormalizer;
use NaijaTel\Services\PhoneNumberValidator;
use NaijaTel\Support\NetworkPrefixRepository;
use PHPUnit\Framework\TestCase;

class PhoneNumberNormalizerTest extends TestCase
{
    private PhoneNumberNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $validator = new PhoneNumberValidator(new NetworkPrefixRepository);
        $this->normalizer = new PhoneNumberNormalizer($validator);
    }

    public function test_it_normalizes_various_formats_to_e164(): void
    {
        $inputs = [
            ['input' => '08031234567', 'expected' => '+2348031234567'],
            ['input' => '+2348031234567', 'expected' => '+2348031234567'],
            ['input' => '2348031234567', 'expected' => '+2348031234567'],
            ['input' => '8031234567', 'expected' => '+2348031234567'],
            ['input' => '0803 123 4567', 'expected' => '+2348031234567'],
            ['input' => '0803-123-4567', 'expected' => '+2348031234567'],
            ['input' => '+234 803 123 4567', 'expected' => '+2348031234567'],
            ['input' => '(0803) 123-4567', 'expected' => '+2348031234567'],
        ];

        foreach ($inputs as $case) {
            $this->assertSame($case['expected'], $this->normalizer->normalize($case['input']));
        }
    }

    public function test_it_parses_into_phone_number_dto(): void
    {
        $parsed = $this->normalizer->parse('08031234567');

        $this->assertInstanceOf(PhoneNumber::class, $parsed);
        $this->assertSame('08031234567', $parsed->raw);
        $this->assertSame('+2348031234567', $parsed->normalized);
        $this->assertSame('0803 123 4567', $parsed->national);
        $this->assertSame('+234 803 123 4567', $parsed->international);
        $this->assertSame('+234', $parsed->countryCode);
        $this->assertSame('8031234567', $parsed->nationalSignificantNumber);
        $this->assertSame('NG', $parsed->countryIso);
        $this->assertTrue($parsed->isValid);
        $this->assertSame('+234 803 *** *567', $parsed->masked);
    }

    public function test_format_national(): void
    {
        $this->assertSame('0803 123 4567', $this->normalizer->formatNational('+2348031234567'));
        $this->assertSame('0803 123 4567', $this->normalizer->formatNational('8031234567'));
    }

    public function test_format_international(): void
    {
        $this->assertSame('+234 803 123 4567', $this->normalizer->formatInternational('08031234567'));
    }

    public function test_get_country_code(): void
    {
        $this->assertSame('+234', $this->normalizer->getCountryCode('08031234567'));
        $this->assertSame('+234', $this->normalizer->getCountryCode('+2348031234567'));
        $this->assertNull($this->normalizer->getCountryCode('invalid'));
    }

    public function test_throws_exception_on_invalid_normalization(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        $this->normalizer->normalize('08001234567');
    }
}
