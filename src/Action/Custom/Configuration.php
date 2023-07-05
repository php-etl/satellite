<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\Custom;

use Kiboko\Component\Satellite\DependencyInjection\Configuration\ServicesConfiguration;
use Kiboko\Contract\Configurator\ActionConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Configuration implements ActionConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('custom');
        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->append((new ServicesConfiguration())->getConfigTreeBuilder()->getRootNode())
                    ->scalarNode('use')
                        ->isRequired()
                    ->end()
                    ->arrayNode('parameters')
                        ->useAttributeAsKey('keyparam')
                        ->scalarPrototype()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(isExpression())
                            ->then(asExpression())
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
