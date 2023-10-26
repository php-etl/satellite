<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class Workflow implements SatelliteInterface, WorkflowInterface
{
    public function __construct(
        private string $label,
        private string $code,
        private JobList $jobs,
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

    public function composer(): Composer
    {
        return $this->composer;
    }

    public function jobs(): JobList
    {
        return $this->jobs;
    }
}
