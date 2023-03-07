<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Workflow\Action;

use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /** @var array<string, Configurator\ActionConfigurationInterface> */
    private iterable $actions = [];

    public function addAction(string $name, Configurator\ActionConfigurationInterface $action): self
    {
        $this->actions[$name] = $action;

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('action');
        $node = $builder->getRootNode();

        /* @phpstan-ignore-next-line */
        foreach ($this->actions as $action) {
            /* @phpstan-ignore-next-line */
            $node->append($action->getConfigTreeBuilder()->getRootNode());
        }

        return $builder;
    }
}
