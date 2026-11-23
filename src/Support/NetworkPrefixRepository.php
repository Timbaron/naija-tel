<?php

declare(strict_types=1);

namespace NaijaTel\Support;

use NaijaTel\Enums\Network;

class NetworkPrefixRepository
{
    /**
     * @var array<string, string>|null
     */
    private ?array $prefixes = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $metadata = null;

    public function __construct(
        private readonly ?string $dataPath = null
    ) {}

    /**
     * Match a phone number or prefix to an allocated Network enum using longest-prefix matching.
     *
     * @param  string  $digits  Digits representing either a full national number (e.g. 08031234567)
     *                          or a prefix (e.g. 0803, 07025)
     */
    public function match(string $digits): Network
    {
        $this->ensureLoaded();

        $clean = preg_replace('/\D/', '', $digits) ?? '';

        // If it starts with Nigerian country code 234, convert to national trunk 0
        if (str_starts_with($clean, '234')) {
            $clean = '0'.substr($clean, 3);
        } elseif (! str_starts_with($clean, '0') && (strlen($clean) === 10 || strlen($clean) === 3 || strlen($clean) === 4)) {
            // E.g. 8031234567 or 803
            $clean = '0'.$clean;
        }

        // Longest prefix match: test lengths 5 down to 3
        $lengths = [5, 4, 3];
        foreach ($lengths as $length) {
            if (strlen($clean) >= $length) {
                $candidate = substr($clean, 0, $length);
                if (isset($this->prefixes[$candidate])) {
                    return Network::tryFromString($this->prefixes[$candidate]);
                }
            }
        }

        return Network::UNKNOWN;
    }

    /**
     * Check whether a specific prefix exists in the database.
     */
    public function hasPrefix(string $prefix): bool
    {
        $this->ensureLoaded();

        $normalized = $this->normalizePrefix($prefix);

        return isset($this->prefixes[$normalized]);
    }

    /**
     * Get all prefixes associated with a given network.
     *
     * @return array<int, string>
     */
    public function getPrefixesForNetwork(Network $network): array
    {
        $this->ensureLoaded();

        $matching = [];
        $prefixes = $this->prefixes ?? [];
        foreach ($prefixes as $prefix => $netStr) {
            if (Network::tryFromString($netStr) === $network) {
                $matching[] = $prefix;
            }
        }

        sort($matching);

        return $matching;
    }

    /**
     * Get all loaded prefixes and their network allocations.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $this->ensureLoaded();

        return $this->prefixes ?? [];
    }

    /**
     * Get dataset metadata (source, verification date, etc.).
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        $this->ensureLoaded();

        return $this->metadata ?? [];
    }

    /**
     * Normalize prefix to standard 0-leading form.
     */
    public function normalizePrefix(string $prefix): string
    {
        $clean = preg_replace('/\D/', '', $prefix) ?? '';
        if (str_starts_with($clean, '234')) {
            $clean = '0'.substr($clean, 3);
        } elseif (! str_starts_with($clean, '0')) {
            $clean = '0'.$clean;
        }

        return $clean;
    }

    /**
     * Ensure prefix dataset is loaded into memory.
     */
    private function ensureLoaded(): void
    {
        if ($this->prefixes !== null) {
            return;
        }

        $path = $this->dataPath ?? dirname(__DIR__, 2).'/resources/data/nigeria-prefixes.php';

        if (file_exists($path)) {
            $data = require $path;
            $this->prefixes = is_array($data['prefixes'] ?? null) ? $data['prefixes'] : [];
            $this->metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        } else {
            $this->prefixes = [];
            $this->metadata = [];
        }
    }
}
