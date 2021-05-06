<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('batch');

        $builder->getRootNode()
            ->children()
                ->arrayNode('merge')
                    ->children()
                        ->integerNode('size')->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
