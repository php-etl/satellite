<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Kiboko\Contract\Pipeline\LoaderInterface;
use Kiboko\Contract\Pipeline\PipelineInterface;
use Kiboko\Contract\Pipeline\RejectionInterface;
use Kiboko\Contract\Pipeline\StateInterface;
use Kiboko\Component\Satellite\Console\StateOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class PipelineConsoleRuntime implements PipelineRuntimeInterface
{
    private StateOutput\Pipeline $state;
    private Container $container;

    public function __construct(
        ConsoleOutput $output,
        private PipelineInterface $pipeline,
        ?ContainerInterface $container = null
    ) {
        $this->state = new StateOutput\Pipeline($output, 'A', 'Pipeline');
        $this->container = $container ?? new Container();
    }

    public function extract(
        ExtractorInterface $extractor,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->pipeline->extract($extractor, $rejection, $state = new MemoryState($state));

        $this->state->withStep('extractor')
            ->addMetric('read', $state->observeAccept())
            ->addMetric('error', fn () => 0)
            ->addMetric('rejected', $state->observeReject());

        return $this;
    }

    public function transform(
        TransformerInterface $transformer,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->pipeline->transform($transformer, $rejection, $state = new MemoryState($state));

        $this->state->withStep('transformer')
            ->addMetric('read', $state->observeAccept())
            ->addMetric('error', fn () => 0)
            ->addMetric('rejected', $state->observeReject());

        return $this;
    }

    public function load(
        LoaderInterface $loader,
        RejectionInterface $rejection,
        StateInterface $state,
    ): self {
        $this->pipeline->load($loader, $rejection, $state = new MemoryState($state));

        $this->state->withStep('loader')
            ->addMetric('read', $state->observeAccept())
            ->addMetric('error', fn () => 0)
            ->addMetric('rejected', $state->observeReject());

        return $this;
    }

    public function run(int $interval = 1000): int
    {
        $line = 0;
        foreach ($this->pipeline->walk() as $item) {
            if ($line++ % $interval === 0) {
                $this->state->update();
            }
        };
        $this->state->update();

        return $line;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
