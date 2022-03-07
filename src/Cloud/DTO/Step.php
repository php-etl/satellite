<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Step
{
    public function __construct(
        public string $name,
        public string $code,
        public array $config = [],
        public array $probes = [],
    ) {}
}
