<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class StepList implements \Countable, \IteratorAggregate
{
    /** @var list<Step> */
    private array $steps;

    public function __construct(
        Step ...$steps,
    ) {
        $this->steps = $steps;
    }

    public function getIterator()
    {
        $steps = $this->steps;
        usort($steps, fn (Step $left, Step $right) => $left->order <=> $right->order);
        return new \ArrayIterator($steps);
    }

    public function codes()
    {
        $steps = $this->steps;
        usort($steps, fn (Step $left, Step $right) => $left->order <=> $right->order);
        return array_map(fn (Step $step) => $step->code->asString(), $steps);
    }

    public function get(string $code): Step
    {
        foreach ($this->steps as $step) {
            if ($step->code->asString() === $code) {
                return $step;
            }
        }

        throw new \OutOfBoundsException('There was no step found matching the provided code');
    }

    public function count()
    {
        return \count($this->steps);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->steps);
    }
}
