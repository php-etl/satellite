<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\GoogleCloudFunction;

use Kiboko\Component\Satellite\Adapter\AdapterConfigurationInterface;
use Kiboko\Contract\Configurator\Adapter;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

#[Adapter(name: "google_cloud_function")]
final class Configuration implements AdapterConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('google_cloud_function');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
            ->end();

        return $builder;
    }
}
