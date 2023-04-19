<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Configuration;

use Kiboko\Component\Satellite\DependencyInjection\Configuration\ServicesConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Reject implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('reject');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->arrayPrototype()
                ->children()
                    ->scalarNode('when')
                        ->isRequired()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
