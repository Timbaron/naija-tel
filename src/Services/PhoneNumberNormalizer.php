<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use NaijaTel\Contracts\PhoneNumberParserInterface;
use NaijaTel\Data\PhoneNumber;
use NaijaTel\Exceptions\InvalidPhoneNumberException;
use NaijaTel\Exceptions\UnsupportedCountryException;
use NaijaTel\Support\PhoneNumberFormatter;

class PhoneNumberNormalizer implements PhoneNumberParserInterface
{
    public function __construct(
        private readonly PhoneNumberValidator $validator
    ) {}

    /**
     * Normalize a valid Nigerian phone number to canonical E.164 format (+2348031234567).
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function normalize(string $phoneNumber, string $defaultCountry = 'NG'): string
    {
        $this->validator->validate($phoneNumber, $defaultCountry);

        $digits = preg_replace('/\D/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '234')) {
            $nationalSignificantNumber = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $nationalSignificantNumber = substr($digits, 1);
        } else {
            $nationalSignificantNumber = $digits;
        }

        return '+234'.$nationalSignificantNumber;
    }

    /**
     * Parse phone number into a strongly-typed PhoneNumber DTO.
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function parse(string $phoneNumber, string $defaultCountry = 'NG'): PhoneNumber
    {
        $normalized = $this->normalize($phoneNumber, $defaultCountry);
        $nsn = substr($normalized, 4); // Remove '+234'
        $national = '0'.$nsn;

        return new PhoneNumber(
            raw: $phoneNumber,
            normalized: $normalized,
            national: PhoneNumberFormatter::national($national),
            international: PhoneNumberFormatter::international($normalized),
            countryCode: '+234',
            nationalSignificantNumber: $nsn,
            countryIso: 'NG',
            isValid: true,
            masked: PhoneNumberFormatter::mask($normalized),
        );
    }

    /**
     * Check if a phone number is valid.
     */
    public function isValid(string $phoneNumber, string $defaultCountry = 'NG'): bool
    {
        return $this->validator->isValid($phoneNumber, $defaultCountry);
    }

    /**
     * Validate the phone number.
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function validate(string $phoneNumber, string $defaultCountry = 'NG'): void
    {
        $this->validator->validate($phoneNumber, $defaultCountry);
    }

    /**
     * Format into national standard format (e.g. 0803 123 4567).
     */
    public function formatNational(string $phoneNumber): string
    {
        $normalized = $this->normalize($phoneNumber);
        $nsn = substr($normalized, 4);

        return PhoneNumberFormatter::national('0'.$nsn);
    }

    /**
     * Format into international standard format (e.g. +234 803 123 4567).
     */
    public function formatInternational(string $phoneNumber): string
    {
        $normalized = $this->normalize($phoneNumber);

        return PhoneNumberFormatter::international($normalized);
    }

    /**
     * Extract the country calling code.
     */
    public function getCountryCode(string $phoneNumber): ?string
    {
        $trimmed = trim($phoneNumber);
        if (str_starts_with($trimmed, '+234') || str_starts_with($trimmed, '234')) {
            return '+234';
        }

        if ($this->validator->isValid($phoneNumber)) {
            return '+234';
        }

        return null;
    }
}
