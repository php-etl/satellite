<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;
use Kiboko\Component\Satellite\Plugin\SFTP\Builder;

final class Repository implements Configurator\StepRepositoryInterface
{
    public function __construct(private Builder\Loader|Builder\Server $builder)
    {
    }

    public function addFiles(Packaging\FileInterface|Packaging\DirectoryInterface ...$files): Repository
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

    public function getBuilder(): Builder\Loader|Builder\Server
    {
        return $this->builder;
    }

    public function merge(Configurator\RepositoryInterface $friend): Repository
    {
        return $this;
    }
}
