<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class CommandBatch implements \Countable, \IteratorAggregate
{
    /** @var list<object> */
    private array $commands;

    public function __construct(
        object ...$commands
    ) {
        $this->commands = $commands;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->commands);
    }

    public function count(): int
    {
        return \count($this->commands);
    }

    public function push(object ...$commands): self
    {
        array_push($this->commands, ...$commands);

        return $this;
    }

    public function merge(self $friend): self
    {
        return new self(...$this->commands, ...$friend->commands);
    }
}
