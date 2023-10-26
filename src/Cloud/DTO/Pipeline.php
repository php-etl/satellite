<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class Pipeline implements SatelliteInterface, PipelineInterface
{
    public function __construct(
        private string $label,
        private string $code,
        private StepList $steps,
        private Composer $composer,
    ) {}

    public function code(): string
    {
        return $this->code;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function steps(): StepList
    {
        return $this->steps;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }
}
