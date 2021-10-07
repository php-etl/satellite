<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Component\Satellite\Console\StateOutput;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\WalkableInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class APIConsoleRuntime implements RunnableInterface
{
    private StateOutput\Pipeline $state;

    public function __construct(
        ConsoleOutput $output,
        private WalkableInterface $api,
        private ContainerInterface $container
    ) {
//        $this->state = new StateOutput\Pipeline($output, 'A', 'Pipeline');
    }

    public function run(int $interval = 1000): int
    {
        $line = 0;
        foreach ($this->api->walk() as $item) {
            if ($line++ % $interval === 0) {
//                $this->state->update();
            }
        };
//        $this->state->update();

        return $line;
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
