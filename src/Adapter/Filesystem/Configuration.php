<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('filesystem');

        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
            ->end();

        return $builder;
    }
}
