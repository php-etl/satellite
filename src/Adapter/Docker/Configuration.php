<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Contract\Configurator\AdapterConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements AdapterConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('docker');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('from')->end()
                ->scalarNode('workdir')->end()
                ->arrayNode('tags')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('copy')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('from')->isRequired()->end()
                            ->scalarNode('to')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
