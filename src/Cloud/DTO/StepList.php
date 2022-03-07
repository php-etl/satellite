<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class StepList implements \Countable, \IteratorAggregate
{
    /** @var Step */
    private array $steps;

    public function __construct(
        Step ...$steps,
    ) {
        $this->steps = $steps;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->steps);
    }

    public function count()
    {
        return \count($this->steps);
    }
}
