<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\StateOutput;

final class Hook
{
    /** @var list<PipelineStep> */
    private array $steps = [];

    public function __construct(
        string $index,
        string $label,
    ) {
    }

    public function withStep(string $label): PipelineStep
    {
        return $this->steps[] = new PipelineStep($this->output, count($this->steps) + 1, $label);
    }

    public function update(): void
    {
        foreach ($this->steps as $step) {
            $step->update();
        }
    }
}
