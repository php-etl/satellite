<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\SFTP\Factory\Repository;

use Kiboko\Component\Satellite\Action\SFTP\Builder;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;

final class Repository implements Configurator\StepRepositoryInterface
{
    public function __construct(private Builder\Action|Builder\Server $builder)
    {
    }

    public function addFiles(Packaging\FileInterface|Packaging\DirectoryInterface ...$files): self
    {
        return $this;
    }

    public function getFiles(): iterable
    {
        return new \EmptyIterator();
    }

    public function addPackages(string ...$packages): self
    {
        return $this;
    }

    public function getPackages(): iterable
    {
        return new \EmptyIterator();
    }

    public function getBuilder(): Builder\Action|Builder\Server
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        return $this;
    }
}
