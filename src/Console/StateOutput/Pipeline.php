<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\StateOutput;

use Symfony\Component\Console\Output\ConsoleOutput;

final class Pipeline
{
    /** @var list<PipelineStep> */
    private array $steps = [];

    public function __construct(
        private ConsoleOutput $output
    ) {}

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
