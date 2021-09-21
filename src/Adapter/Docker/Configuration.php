<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Satellite\Adapter\AdapterConfigurationInterface;
use Kiboko\Contract\Configurator\Adapter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

#[Adapter("docker")]
final class Configuration implements AdapterConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('docker');

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
