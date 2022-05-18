<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Autoload implements \Countable, \IteratorAggregate
{
    public array $autoloads;

    public function __construct(
        PSR4AutoloadConfig ...$autoloads,
    ) {
        $this->autoloads = $autoloads;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->autoloads);
    }

    public function count(): int
    {
        return \count($this->autoloads);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->autoloads);
    }
}
