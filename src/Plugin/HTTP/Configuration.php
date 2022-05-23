<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\HTTP;

use Kiboko\Contract\Configurator\PluginConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements PluginConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('http');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('extractor')
                    ->children()
                        ->scalarNode('url')->end()
                        ->enumNode('method')->values(['GET', 'DELETE'])->end()
                        ->arrayNode('headers')
                            ->arrayPrototype()
                                ->useAttributeAsKey('name')
                                ->children()
                                    ->scalarNode('name')->end()
                                    ->arrayNode('values')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('loader')
                    ->children()
                        ->scalarNode('url')->end()
                        ->enumNode('method')->values(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])->end()
                        ->booleanNode('item_as_body')->end()
                        ->enumNode('encoding')->values(['json'])->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
