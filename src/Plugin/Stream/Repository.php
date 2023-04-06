<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream;

use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;

final readonly class Repository implements Configurator\StepRepositoryInterface
{
    public function __construct(
        private Builder\StderrLoader|Builder\StdoutLoader|Builder\JSONStreamLoader|Builder\DebugLoader $builder
    ) {
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

    public function getBuilder(): Builder\StderrLoader|Builder\StdoutLoader|Builder\JSONStreamLoader|Builder\DebugLoader
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        return $this;
    }
}
