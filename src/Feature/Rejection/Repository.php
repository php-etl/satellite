<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection;

use Kiboko\Contract\Configurator;

final class Repository implements Configurator\RepositoryInterface
{
    use RepositoryTrait;

    public function __construct(private Builder\Rejection $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Builder\Rejection
    {
        return $this->builder;
    }
}
