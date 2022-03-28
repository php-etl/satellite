<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Probe
{
    public function __construct(
        public string $label,
        public string $code,
        public int $order,
    ) {}
}
