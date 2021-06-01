<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('batch');

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
            ->end();

        return $builder;
    }
}
