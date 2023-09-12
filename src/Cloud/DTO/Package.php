<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Package
{
    public function __construct(
        public string $name,
        public string $version,
    ) {}
}
