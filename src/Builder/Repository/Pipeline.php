<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\Repository;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;

final class Pipeline implements Configurator\RepositoryInterface
{
    /** @var Packaging\FileInterface[] */
    private array $files = [];
    /** @var string[] */
    private array $packages = [];

    public function __construct(private readonly Satellite\Builder\Pipeline $builder)
    {
    }

    public function addFiles(Packaging\DirectoryInterface|Packaging\FileInterface ...$files): Configurator\RepositoryInterface
    {
        array_push($this->files, ...$files);

        return $this;
    }

    /** @return iterable<Packaging\FileInterface> */
    public function getFiles(): iterable
    {
        return $this->files;
    }

    public function addPackages(string ...$packages): Configurator\RepositoryInterface
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    /** @return iterable<string> */
    public function getPackages(): iterable
    {
        return $this->packages;
    }

    public function getBuilder(): Satellite\Builder\Pipeline
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): Configurator\RepositoryInterface
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
