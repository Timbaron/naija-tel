<?php

declare(strict_types=1);

namespace NaijaTel\Enums;

enum Network: string
{
    case MTN = 'mtn';
    case AIRTEL = 'airtel';
    case GLO = 'glo';
    case NINE_MOBILE = '9mobile';
    case UNKNOWN = 'unknown';

    /**
     * Get human-readable brand label for the network operator.
     */
    public function label(): string
    {
        return match ($this) {
            self::MTN => 'MTN Nigeria',
            self::AIRTEL => 'Airtel Nigeria',
            self::GLO => 'Globacom (Glo)',
            self::NINE_MOBILE => '9mobile',
            self::UNKNOWN => 'Unknown Network',
        };
    }

    /**
     * Determine if this is a known, identified network operator.
     */
    public function isKnown(): bool
    {
        return $this !== self::UNKNOWN;
    }

    /**
     * Return all supported recognized mobile network values.
     *
     * @return array<int, string>
     */
    public static function supportedValues(): array
    {
        return [
            self::MTN->value,
            self::AIRTEL->value,
            self::GLO->value,
            self::NINE_MOBILE->value,
        ];
    }

    /**
     * Parse a loose or raw network string into a typed Network enum.
     */
    public static function tryFromString(?string $value): self
    {
        if ($value === null || trim($value) === '') {
            return self::UNKNOWN;
        }

        $cleaned = strtolower(trim($value));

        return match ($cleaned) {
            'mtn', 'mtn nigeria', 'mtn ng' => self::MTN,
            'airtel', 'airtel nigeria', 'airtel ng', 'celtel', 'vmobile', 'econet' => self::AIRTEL,
            'glo', 'globacom', 'glo ng', 'globacom nigeria' => self::GLO,
            '9mobile', 'etisalat', 'etisalat nigeria', 'emerging markets' => self::NINE_MOBILE,
            default => self::tryFrom($cleaned) ?? self::UNKNOWN,
        };
    }
}
