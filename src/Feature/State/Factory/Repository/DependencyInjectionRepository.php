<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite\Feature\State;
use PhpParser\Node;

final class DependencyInjectionRepository implements Configurator\RepositoryInterface
{
    use State\RepositoryTrait;

    public function __construct(
        private State\Builder\DependencyInjectionBuilder $builder
    ) {
        $this->files = [];
        $this->packages = [];
    }

    public function withStepInfo(Node\Expr $stepName, Node\Expr $stepCode): self
    {
        $this->builder->withStepInfo($stepName, $stepCode);

        return $this;
    }

    public function getBuilder(): State\Builder\DependencyInjectionBuilder
    {
        return $this->builder;
    }
}
