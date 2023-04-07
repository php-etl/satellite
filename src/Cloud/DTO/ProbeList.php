<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class ProbeList
{
    /** @var list<Probe> */
    private readonly array $probes;

    public function __construct(
        Probe ...$probes,
    ) {
        $this->probes = $probes;
    }

    public function getIterator()
    {
        $probes = $this->probes;
        usort($probes, fn (Probe $left, Probe $right) => $left->order <=> $right->order);

        return new \ArrayIterator($probes);
    }

    public function count()
    {
        return \count($this->probes);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->probes);
    }
}
