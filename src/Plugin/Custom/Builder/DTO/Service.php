<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Builder\DTO;

final class Service
{
    public function __construct(
        public string $identifier,
        public iterable $arguments = [],
        public iterable $calls = [],
    ) {
    }
}
