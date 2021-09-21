<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Satellite\Adapter\AdapterConfigurationInterface;
use Kiboko\Contract\Configurator\Adapter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

#[Adapter("filesystem")]
final class Configuration implements AdapterConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('filesystem');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
                ->arrayNode('copy')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('from')->isRequired()->end()
                            ->scalarNode('to')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
