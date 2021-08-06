<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Serverless;

use Kiboko\Component\Satellite\NamedConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements NamedConfigurationInterface
{
    public function getName(): string
    {
        return 'serverless';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('layers')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('provider')
                    ->children()
                        ->scalarNode('name')->isRequired()->end()
                        ->scalarNode('runtime')->end()
                        ->scalarNode('stage')->end()
                        ->scalarNode('region')->end()
                        ->scalarNode('stackName')->end()
                        ->scalarNode('profile')->end()
                        ->scalarNode('memorySize')->end()
                        ->scalarNode('timeout')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
