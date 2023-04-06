<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Configuration;

use Symfony\Component\Config;

final class MemcachedConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder(): \Symfony\Component\Config\Definition\Builder\TreeBuilder
    {
        $builder = new Config\Definition\Builder\TreeBuilder('memcached');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
            ->arrayNode('servers')
            ->fixXmlConfig('server')
            ->requiresAtLeastOneElement()
            ->cannotBeEmpty()
            ->ignoreExtraKeys()
            ->arrayPrototype()
            ->children()
            ->scalarNode('host')->isRequired()->end()
            ->scalarNode('port')->isRequired()->end()
            ->integerNode('timeout')->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $builder;
    }
}
