<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

use Kiboko\Component\Satellite\Cloud\DTO;

readonly class JobList implements \Countable, \IteratorAggregate
{
    private array $jobs;

    public function __construct(
        DTO\Workflow\JobInterface ...$job,
    ) {
        $this->jobs = $job;
    }

    public function getIterator(): \Traversable
    {
        $jobs = $this->jobs;

        /* @phpstan-ignore-next-line */
        usort($jobs, fn (DTO\Workflow\JobInterface $left, DTO\Workflow\JobInterface $right) => $left->order <=> $right->order);

        return new \ArrayIterator($jobs);
    }

    public function codes(): array
    {
        $jobs = $this->jobs;

        /* @phpstan-ignore-next-line */
        usort($jobs, fn (DTO\Workflow\JobInterface $left, DTO\Workflow\JobInterface $right) => $left->order <=> $right->order);

        /* @phpstan-ignore-next-line */
        return array_map(fn (DTO\Workflow\JobInterface $job) => $job->code->asString(), $jobs);
    }

    public function get(string $code): DTO\Workflow\JobInterface
    {
        foreach ($this->jobs as $job) {
            if ($job->code->asString() === $code) {
                return $job;
            }
        }

        throw new \OutOfBoundsException('There was no job found matching the provided code');
    }

    public function count(): int
    {
        return \count($this->jobs);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->jobs);
    }
}
