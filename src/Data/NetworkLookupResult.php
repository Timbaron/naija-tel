<?php

declare(strict_types=1);

namespace NaijaTel\Data;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use NaijaTel\Enums\DetectionMethod;
use NaijaTel\Enums\LookupStatus;
use NaijaTel\Enums\Network;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class NetworkLookupResult implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $phoneNumber,
        public Network $network,
        public ?Network $originalNetwork = null,
        public ?bool $isPorted = null,
        public bool $verified = false,
        public DetectionMethod $method = DetectionMethod::PREFIX,
        public LookupStatus $status = LookupStatus::INFERRED,
        public ?string $provider = null,
        public DateTimeImmutable $lookupAt = new DateTimeImmutable,
        public array $metadata = [],
    ) {}

    /**
     * Determine if the lookup was successful and resolved to a known network.
     */
    public function isSuccess(): bool
    {
        return $this->network->isKnown() && ! $this->status->isFailure();
    }

    /**
     * Determine if the number is verified to have been ported.
     */
    public function isPorted(): ?bool
    {
        return $this->isPorted;
    }

    /**
     * Determine if the lookup is verified authoritative.
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Convert result to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'phone_number' => $this->phoneNumber,
            'network' => $this->network->value,
            'network_label' => $this->network->label(),
            'original_network' => $this->originalNetwork?->value,
            'original_network_label' => $this->originalNetwork?->label(),
            'is_ported' => $this->isPorted,
            'verified' => $this->verified,
            'method' => $this->method->value,
            'status' => $this->status->value,
            'provider' => $this->provider,
            'lookup_at' => $this->lookupAt->format(DateTimeInterface::ATOM),
            'metadata' => $this->metadata,
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
