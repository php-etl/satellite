<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Configuration;

use Kiboko\Component\Satellite\DependencyInjection\Configuration\ServicesConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Extractor implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('extractor');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
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
            ->end();

        return $builder;
    }
}
