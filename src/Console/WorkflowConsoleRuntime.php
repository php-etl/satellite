<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Component\Satellite\Console\StateOutput;
use Kiboko\Component\Workflow\RunnableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

final class WorkflowConsoleRuntime implements WorkflowRuntimeInterface
{
    private StateOutput\Pipeline $state;
    private array $runtimes = [];

    public function __construct(
        private ConsoleOutput $output,
        private \Kiboko\Component\Workflow\Workflow $workflow
    ) {
        $this->state = new StateOutput\Pipeline($output);
    }

    public function job(PipelineRuntimeInterface $job): self
    {
        $this->runtimes[] = $job;

        return $this;
    }

    public function pipelineRuntime(): PipelineRuntimeInterface
    {
        $pipeline = new \Kiboko\Component\Pipeline\Pipeline(
            new \Kiboko\Component\Pipeline\PipelineRunner(new \Psr\Log\NullLogger())
        );

        $this->workflow->job($pipeline);

        return new PipelineConsoleRuntime(
            $this->output,
            $pipeline
        );
    }

    public function run(): void
    {
        $this->workflow->run();
    }
}
