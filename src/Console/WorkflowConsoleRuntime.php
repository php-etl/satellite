<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Component\Pipeline\Pipeline;
use Kiboko\Component\Satellite\Console\StateOutput;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\SchedulingInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class WorkflowConsoleRuntime implements WorkflowRuntimeInterface
{
    private StateOutput\Workflow $state;
    private Container $container;

    public function __construct(
        private ConsoleOutput $output,
        private SchedulingInterface $workflow,
        private PipelineRunnerInterface $pipelineRunner,
        ?ContainerInterface $container = null
    ) {
        $this->state = new StateOutput\Workflow($output);
        $this->container = $container ?? new Container();
    }

    public function loadPipeline(string $filename): Workflow\PipelineConsoleRuntime
    {
        $factory = require $filename;

        $pipeline = new Pipeline($this->pipelineRunner);
        $this->workflow->job($pipeline);

        return $factory(new Workflow\PipelineConsoleRuntime($this->output, $pipeline, $this->state->withPipeline(basename($filename))));
    }

    public function job(RunnableInterface $job): self
    {
        $this->workflow->job($job);

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $count = 0;
        foreach ($this->workflow->walk() as $job) {
            $count = $job->run($interval);
        }
        return $count;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
