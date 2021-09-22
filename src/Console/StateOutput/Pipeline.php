<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\StateOutput;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

final class Pipeline
{
    /** @var list<PipelineStep> */
    private array $steps = [];
    private ConsoleSectionOutput $section;

    public function __construct(
        private ConsoleOutput $output,
        string $index,
        string $label,
    ) {
        $this->section = $output->section();
        $this->section->writeln('');
        $this->section->writeln(sprintf('<fg=green> % 2s. %-50s</>', $index, $label));
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
