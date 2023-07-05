<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\Custom\Factory\Repository;

use Kiboko\Component\Satellite\Action\Custom;
use Kiboko\Contract\Configurator;

final class Action implements Configurator\RepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private readonly Custom\Builder\Action $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Custom\Builder\Action
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
