<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Kiboko\Contract\Configurator;

final class Repository implements Configurator\RepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private Builder\State $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Builder\State
    {
        return $this->builder;
    }
}
