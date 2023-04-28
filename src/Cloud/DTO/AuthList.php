<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class AuthList implements \Countable, \IteratorAggregate
{
    public array $auths;

    public function __construct(
        Auth ...$auth,
    ) {
        $this->auths = $auth;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->auths);
    }

    public function count(): int
    {
        return \count($this->auths);
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->auths);
    }
}
