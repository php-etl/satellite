<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Symfony\Component\Console\Output\ConsoleOutput;

final class WorkflowConsoleRuntime implements WorkflowRuntimeInterface
{
    private array $jobs = [];

    public function __construct(
        private ConsoleOutput $output,
        private \Kiboko\Component\Workflow\Workflow $workflow
    )
    {
    }

    public function job(PipelineRuntimeInterface $job): self
    {
        $this->jobs[] = $job;

        return $this;
    }

    public function pipelineRuntime(): PipelineRuntimeInterface
    {

        $pipeline = new \Kiboko\Component\Pipeline\Pipeline(
            new \Kiboko\Component\Pipeline\PipelineRunner(new \Psr\Log\NullLogger())
        );

        $this->workflow->job($pipeline);

        $runtime = new PipelineConsoleRuntime(
            $this->output,
            $pipeline
        );

        $runtime->run();

        return $runtime;
    }

    public function run(): void
    {
        $this->workflow->run();

        foreach ($this->jobs as $job) {
            $job->run();
        }
    }
}
