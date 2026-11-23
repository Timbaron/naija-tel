<?php

declare(strict_types=1);

namespace NaijaTel\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Stringable;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class PhoneNumber implements Arrayable, JsonSerializable, Stringable
{
    public function __construct(
        public string $raw,
        public string $normalized,
        public string $national,
        public string $international,
        public string $countryCode,
        public string $nationalSignificantNumber,
        public string $countryIso,
        public bool $isValid,
        public string $masked,
    ) {}

    /**
     * Get the phone number as its normalized E.164 string.
     */
    public function __toString(): string
    {
        return $this->normalized;
    }

    /**
     * Convert the PhoneNumber DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'raw' => $this->raw,
            'normalized' => $this->normalized,
            'national' => $this->national,
            'international' => $this->international,
            'country_code' => $this->countryCode,
            'national_significant_number' => $this->nationalSignificantNumber,
            'country_iso' => $this->countryIso,
            'is_valid' => $this->isValid,
            'masked' => $this->masked,
        ];
    }

    /**
     * Serialize for JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
