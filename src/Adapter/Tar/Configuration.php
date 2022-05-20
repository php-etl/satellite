<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Tar;

use Kiboko\Contract\Configurator\AdapterConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements AdapterConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('tar');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
            ->scalarNode('output')->end()
            ->end()
        ;

        return $builder;
    }
}
