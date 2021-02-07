<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Api;

use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Satellite\NamedConfigurationInterface
{
    public function getName(): string
    {
        return 'http_api';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
                ->arrayNode('routes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('route')->end()
                            ->append((new Satellite\Runtime\Pipeline\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
