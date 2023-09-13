<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Repository
{
    public function __construct(
        public string $name,
        public string $type,
        public string $url,
    ) {}
}
