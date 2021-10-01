<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\StateOutput;

use Symfony\Component\Console\Output\ConsoleOutput;

final class Workflow
{
    /** @var list<Pipeline> */
    private array $pipelines = [];
    private string $index = 'A';

    public function __construct(
        private ConsoleOutput $output,
    ) {
    }

    public function withPipeline(string $label): Pipeline
    {
        return $this->pipelines[] = new Pipeline($this->output, $this->index++, $label);
    }

    public function update(): void
    {
        foreach ($this->pipelines as $pipeline) {
            $pipeline->update();
        }
    }
}
