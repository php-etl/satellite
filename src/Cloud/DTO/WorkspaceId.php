<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class WorkspaceId implements \Stringable
{
    public function __construct(
        private string $reference,
    ) {
    }

    public function isNil(): bool
    {
        return $this->reference === '00000000-0000-0000-0000-000000000000';
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
