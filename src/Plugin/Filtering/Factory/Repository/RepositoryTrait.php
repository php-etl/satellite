<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;

trait RepositoryTrait
{
    /** @var array<Packaging\FileInterface|Packaging\DirectoryInterface> */
    private array $files;
    /** @var string[] */
    private array $packages;

    public function addFiles(Packaging\DirectoryInterface|Packaging\FileInterface ...$files): Configurator\RepositoryInterface
    {
        array_push($this->files, ...$files);

        return $this;
    }

    /** @return iterable<Packaging\FileInterface|Packaging\DirectoryInterface> */
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
}
