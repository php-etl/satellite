<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Autoload implements \Countable, \IteratorAggregate
{
    public array $autoloads;

    public function __construct(
        PSR4AutoloadConfig ...$autoloads,
    ) {
        $this->autoloads = $autoloads;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->autoloads);
    }

    public function count()
    {
        return \count($this->autoloads);
    }
}
