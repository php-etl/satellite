<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger;

use Kiboko\Contract\Configurator;

final class Repository implements Configurator\RepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private Builder\Logger $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Builder\Logger
    {
        return $this->builder;
    }
}
