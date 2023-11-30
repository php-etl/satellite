<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger;

use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;

trait RepositoryTrait
{
    /** @var Packaging\FileInterface[] */
    private array $files;
    /** @var string[] */
    private array $packages;

    public function addFiles(Packaging\DirectoryInterface|Packaging\FileInterface ...$files): self
    {
        array_push($this->files, ...$files);

        return $this;
    }

    /** @return iterable<Packaging\FileInterface> */
    public function getFiles(): iterable
    {
        return $this->files;
    }

    public function addPackages(string ...$packages): self
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    /** @return iterable<string> */
    public function getPackages(): iterable
    {
        return $this->packages;
    }

    public function merge(Configurator\RepositoryInterface $friend): self
    {
        array_push($this->files, ...$friend->getFiles());
        array_push($this->packages, ...$friend->getPackages());

        return $this;
    }
}
