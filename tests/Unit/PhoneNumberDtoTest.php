<?php

declare(strict_types=1);

namespace NaijaTel\Tests\Unit;

use NaijaTel\Data\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberDtoTest extends TestCase
{
    public function test_it_instantiates_and_serializes_phone_number_dto(): void
    {
        $dto = new PhoneNumber(
            raw: '0803 123 4567',
            normalized: '+2348031234567',
            national: '0803 123 4567',
            international: '+234 803 123 4567',
            countryCode: '+234',
            nationalSignificantNumber: '8031234567',
            countryIso: 'NG',
            isValid: true,
            masked: '+234 803 *** *567'
        );

        $this->assertSame('+2348031234567', (string) $dto);
        $this->assertSame('+2348031234567', $dto->normalized);
        $this->assertSame('0803 123 4567', $dto->national);
        $this->assertSame('+234 803 123 4567', $dto->international);
        $this->assertSame('+234', $dto->countryCode);
        $this->assertSame('8031234567', $dto->nationalSignificantNumber);
        $this->assertSame('NG', $dto->countryIso);
        $this->assertTrue($dto->isValid);
        $this->assertSame('+234 803 *** *567', $dto->masked);

        $array = $dto->toArray();
        $this->assertSame('+2348031234567', $array['normalized']);
        $this->assertSame('0803 123 4567', $array['national']);
        $this->assertSame('NG', $array['country_iso']);

        $json = json_encode($dto);
        $this->assertIsString($json);
        $this->assertStringContainsString('+2348031234567', $json);
    }
}
