<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /** @var array<string, ConfigurationInterface> */
    private iterable $plugins = [];
    /** @var array<string, ConfigurationInterface> */
    private iterable $features = [];

    public function addPlugin(string $name, ConfigurationInterface $plugin): self
    {
        $this->plugins[$name] = $plugin;

        return $this;
    }

    public function addFeature(string $name, ConfigurationInterface $feature): self
    {
        $this->features[$name] = $feature;

        return $this;
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('http_hook');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('name')->end()
                ->scalarNode('path')->end()
                ->append((new Satellite\Runtime\Pipeline\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->end();

        return $builder;
    }
}
