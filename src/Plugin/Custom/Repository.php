<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Contract\Configurator\FileInterface;
use Kiboko\Contract\Configurator\RepositoryInterface;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use PhpParser\Builder;

final class Repository implements StepRepositoryInterface
{
    public function __construct(private Builder $builder)
    {
    }

    public function addFiles(FileInterface ...$files): Repository
    {
        return $this;
    }

    public function getFiles(): iterable
    {
        return new \EmptyIterator();
    }

    public function addPackages(string ...$packages): Repository
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

    public function merge(RepositoryInterface $friend): Repository
    {
        return $this;
    }
}
