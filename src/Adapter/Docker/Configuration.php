<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('docker');

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
