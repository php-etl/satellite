<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Satellite\NamedConfigurationInterface
{
    public function getName(): string
    {
        return 'http_hook';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
                ->append((new Satellite\Runtime\Pipeline\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->end();

        return $builder;
    }
}
