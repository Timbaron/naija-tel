<?php

declare(strict_types=1);

namespace NaijaTel\Services;

use NaijaTel\Exceptions\InvalidPhoneNumberException;
use NaijaTel\Exceptions\UnsupportedCountryException;
use NaijaTel\Support\NetworkPrefixRepository;

class PhoneNumberValidator
{
    public function __construct(
        private readonly NetworkPrefixRepository $prefixRepository
    ) {}

    /**
     * Determine if the input is a valid Nigerian mobile phone number.
     */
    public function isValid(string $phoneNumber, string $defaultCountry = 'NG'): bool
    {
        try {
            $this->validate($phoneNumber, $defaultCountry);

            return true;
        } catch (InvalidPhoneNumberException|UnsupportedCountryException) {
            return false;
        }
    }

    /**
     * Validate the input, throwing an exception if invalid.
     *
     * @throws InvalidPhoneNumberException
     * @throws UnsupportedCountryException
     */
    public function validate(string $phoneNumber, string $defaultCountry = 'NG'): void
    {
        $trimmed = trim($phoneNumber);
        if ($trimmed === '') {
            throw InvalidPhoneNumberException::forNumber($phoneNumber, 'Phone number cannot be empty');
        }

        $upperCountry = strtoupper(trim($defaultCountry));
        if ($upperCountry !== 'NG' && $upperCountry !== 'NGA' && $upperCountry !== '+234' && $upperCountry !== '234') {
            throw UnsupportedCountryException::forCountry($defaultCountry);
        }

        // Check for non-Nigerian international prefixes (e.g. +1, +44, etc.)
        if (str_starts_with($trimmed, '+')) {
            if (! str_starts_with($trimmed, '+234')) {
                throw UnsupportedCountryException::forCountry(substr($trimmed, 0, 4));
            }
        }

        // Extract digits
        $digits = preg_replace('/\D/', '', $trimmed) ?? '';

        // If it starts with international prefix 234
        if (str_starts_with($digits, '234')) {
            $nationalPart = substr($digits, 3);
        } elseif (str_starts_with($digits, '0')) {
            $nationalPart = substr($digits, 1);
        } else {
            $nationalPart = $digits;
        }

        // Nigerian national mobile significant number must be exactly 10 digits
        if (strlen($nationalPart) !== 10) {
            throw InvalidPhoneNumberException::forNumber(
                $phoneNumber,
                'Expected 10 national significant digits, got '.strlen($nationalPart)
            );
        }

        // Must start with valid mobile prefix indicator: 7, 8, or 9
        $firstDigit = $nationalPart[0];
        if (! in_array($firstDigit, ['7', '8', '9'], true)) {
            throw InvalidPhoneNumberException::forNumber(
                $phoneNumber,
                "Nigerian mobile numbers must begin with 7, 8, or 9, got {$firstDigit}"
            );
        }

        // Check if prefix is recognized in the national numbering plan
        $nationalPrefix = '0'.substr($nationalPart, 0, 3); // 4-digit e.g. 0803
        $national5Prefix = '0'.substr($nationalPart, 0, 4); // 5-digit e.g. 07025

        if (! $this->prefixRepository->hasPrefix($nationalPrefix) && ! $this->prefixRepository->hasPrefix($national5Prefix)) {
            throw InvalidPhoneNumberException::forNumber(
                $phoneNumber,
                "Prefix [{$nationalPrefix}] is not an allocated mobile network prefix in Nigeria"
            );
        }
    }
}
