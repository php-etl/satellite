<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

use Gyroscops\Api;

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

    public function map(): array
    {
        return array_map(
            fn (PSR4AutoloadConfig $autoloadConfig) => (new Api\Model\AutoloadInput())
                ->setNamespace($autoloadConfig->namespace)
                ->setPaths($autoloadConfig->paths),
            $this->autoloads
        );
    }
}
