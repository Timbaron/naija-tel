<?php

declare(strict_types=1);

namespace NaijaTel\Providers\Custom;

use Closure;
use NaijaTel\Contracts\MnpProviderInterface;
use NaijaTel\Data\NetworkLookupResult;

class CustomMnpProvider implements MnpProviderInterface
{
    /**
     * @param  Closure(string): NetworkLookupResult  $handler
     */
    public function __construct(
        private readonly string $name,
        private readonly Closure $handler
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function lookup(string $phoneNumber): NetworkLookupResult
    {
        return ($this->handler)($phoneNumber);
    }
}
