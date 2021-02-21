<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Satellite\NamedConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements NamedConfigurationInterface
{
    public function getName(): string
    {
        return 'docker';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('from')->end()
                ->scalarNode('workdir')->end()
                ->arrayNode('tags')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $builder;
    }
}
