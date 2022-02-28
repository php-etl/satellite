<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Cloud;

use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Configurator\AdapterConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('cloud');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('url')->isRequired()->end()
                ->scalarNode('project')->isRequired()->end()
            ->end();

        return $builder;
    }
}
