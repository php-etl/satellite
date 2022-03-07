<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class CommandBatch implements \Countable, \IteratorAggregate
{
    /** @var list<object> */
    private array $commands;

    private function __consturct(
        object ...$commands
    ) {
        $this->commands = $commands;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->commands);
    }

    public function count()
    {
        return count($this->commands);
    }
}
