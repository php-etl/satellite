<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class PackageList implements \Countable, \IteratorAggregate
{
    public array $packages;

    public function __construct(
        Package ...$package,
    ) {
        $this->packages = $package;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->packages);
    }

    public function count(): int
    {
        return \count($this->packages);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->packages);
    }
    
    public function transform(): array
    {
        $result = [];
        foreach ($this->packages as $package) {
            $result[$package->name] = $package->version;
        }
        
        return $result;
    }
}
