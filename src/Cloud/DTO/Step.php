<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Step
{
    public function __construct(
        public string $label,
        public StepCode $code,
        public array $config,
        public ProbeList $probes,
        public int $order,
    ) {
    }
}
