<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\StateOutput;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

final class PipelineStep
{
    /** @var array<string, callable> */
    private $metrics;
    private ConsoleSectionOutput $section;

    public function __construct(
        private ConsoleOutput $output,
        int $index,
        string $label,
    ) {
        $this->section = $output->section();
        $this->section->writeln(sprintf('<fg=cyan> % 2d. %-50s</>', $index, $label));
        $this->section->writeln('');
    }

    public function addMetric(string $label, callable $callback): self
    {
        $this->metrics[$label] = $callback;

        return $this;
    }

    public function update(): void
    {
        $this->section->clear(1);
        $this->section
            ->writeln('     '.implode(', ', array_map(
                fn (string $label, callable $callback) => sprintf('%s <fg=cyan>%d</>', $label, ($callback)()),
                array_keys($this->metrics),
                array_values($this->metrics),
            )));
    }
}
