<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\API;

use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\WalkableInterface;

final class API implements WalkableInterface, RunnableInterface
{
    /** @var list<RunnableInterface> */
    private array $jobs = [];

    public function run(): int
    {
        $count = 0;
        foreach ($this->jobs as $job) {
            $count = $job->run();
        }

        return $count;
    }

    public function walk(): \Iterator
    {
        foreach ($this->jobs as $job) {
            yield $job;
        }
    }
}
