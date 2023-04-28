<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class RepositoryList implements \Countable, \IteratorAggregate
{
    public array $repositories;

    public function __construct(
        Repository ...$repository,
    ) {
        $this->repositories = $repository;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->repositories);
    }

    public function count(): int
    {
        return \count($this->repositories);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->repositories);
    }
}
