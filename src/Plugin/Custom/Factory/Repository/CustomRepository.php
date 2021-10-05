<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite\Plugin\Custom;

final class CustomRepository implements Configurator\StepRepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private Custom\Builder\CustomBuilder $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Custom\Builder\CustomBuilder
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
