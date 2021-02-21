<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Contract\Configurator\FileInterface;
use Kiboko\Contract\Configurator\RepositoryInterface;
use PhpParser\Builder;

final class Repository implements RepositoryInterface
{
    public function __construct(private Builder $builder)
    {
    }

    public function addFiles(FileInterface ...$files): RepositoryInterface
    {
        return $this;
    }

    public function getFiles(): iterable
    {
        return new \EmptyIterator();
    }

    public function addPackages(string ...$packages): RepositoryInterface
    {
        return $this;
    }

    public function getPackages(): iterable
    {
        return new \EmptyIterator();
    }

    public function getBuilder(): Builder
    {
        return $this->builder;
    }

    public function merge(RepositoryInterface $friend): RepositoryInterface
    {
        return $this;
    }
}
