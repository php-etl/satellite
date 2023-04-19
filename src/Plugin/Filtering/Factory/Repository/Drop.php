<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Factory\Repository;

use Kiboko\Component\Satellite\Plugin\Filtering;
use Kiboko\Contract\Configurator;

final class Drop implements Configurator\StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private readonly Filtering\Builder\Drop $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Filtering\Builder\Drop
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
