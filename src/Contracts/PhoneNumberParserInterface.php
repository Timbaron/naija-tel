<?php

declare(strict_types=1);

namespace NaijaTel\Contracts;

use NaijaTel\Data\PhoneNumber;

interface PhoneNumberParserInterface
{
    /**
     * Parse phone number string into a structured PhoneNumber object.
     */
    public function parse(string $phoneNumber, string $defaultCountry = 'NG'): PhoneNumber;

    /**
     * Normalize valid Nigerian phone number to E.164 (+2348031234567).
     */
    public function normalize(string $phoneNumber, string $defaultCountry = 'NG'): string;

    /**
     * Check if phone number is valid according to the Nigerian numbering plan.
     */
    public function isValid(string $phoneNumber, string $defaultCountry = 'NG'): bool;

    /**
     * Validate phone number, throwing InvalidPhoneNumberException if invalid.
     */
    public function validate(string $phoneNumber, string $defaultCountry = 'NG'): void;

    /**
     * Format phone number to national standard (e.g. 0803 123 4567).
     */
    public function formatNational(string $phoneNumber): string;

    /**
     * Format phone number to international standard (e.g. +234 803 123 4567).
     */
    public function formatInternational(string $phoneNumber): string;

    /**
     * Extract country calling code (e.g. +234).
     */
    public function getCountryCode(string $phoneNumber): ?string;
}
