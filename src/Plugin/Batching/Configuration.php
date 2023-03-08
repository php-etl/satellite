<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching;

use Kiboko\Contract\Configurator\PluginConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Configuration implements PluginConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('batch');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->variableNode('expression_language')->end()
                ->arrayNode('merge')
                    ->children()
                        ->integerNode('size')->end()
                    ->end()
                ->end()
                ->arrayNode('fork')
                    ->children()
                        ->scalarNode('foreach')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(isExpression())
                                ->then(asExpression())
                            ->end()
                        ->end()
                        ->scalarNode('do')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(isExpression())
                                ->then(asExpression())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
