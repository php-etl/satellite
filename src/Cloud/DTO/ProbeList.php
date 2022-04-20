<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

use Gyroscops\Api;

final class ProbeList
{
    /** @var list<Probe> */
    private array $probes;

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

    public function map(): array
    {
        return array_map(
            fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label),
            $this->probes
        );
    }
}
