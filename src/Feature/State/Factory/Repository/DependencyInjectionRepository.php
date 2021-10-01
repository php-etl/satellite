<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite\Feature\State;

final class DependencyInjectionRepository implements Configurator\RepositoryInterface
{
    use State\RepositoryTrait;

    public function __construct(private State\Builder\DependencyInjectionBuilder $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): State\Builder\DependencyInjectionBuilder
    {
        return $this->builder;
    }
}
