<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class SatelliteId implements \Stringable
{
    public function __construct(
        private string $reference,
    ) {
    }

    public function asString(): string
    {
        return $this->reference;
    }

    public function __toString(): string
    {
        return $this->reference;
    }
}
