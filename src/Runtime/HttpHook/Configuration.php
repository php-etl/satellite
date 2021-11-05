<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Configurator\RuntimeConfigurationInterface
{
    /** @var array<string, Configurator\PluginConfigurationInterface> */
    private iterable $plugins = [];
    /** @var array<string, Configurator\FeatureConfigurationInterface> */
    private iterable $features = [];

    private Satellite\Runtime\Pipeline\Configuration $pipelineConfiguration;

    public function __construct()
    {
        $this->pipelineConfiguration = new Satellite\Runtime\Pipeline\Configuration();
    }

    public function addPlugin(string $name, Configurator\PluginConfigurationInterface $plugin): self
    {
        $this->pipelineConfiguration->addPlugin(
            $name,
            $plugin
        );

        return $this;
    }

    public function addFeature(string $name, Configurator\FeatureConfigurationInterface $feature): self
    {
        $this->pipelineConfiguration->addFeature(
            $name,
            $feature
        );

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
            ->arrayNode('authorization')
                ->children()
                    ->arrayNode('jwt')
                        ->children()
                            ->scalarNode('secret')->end()
                        ->end()
                    ->end()
                    ->arrayNode('basic')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('user')->end()
                                ->scalarNode('password')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('expression')->end()
            ->append($this->pipelineConfiguration->getConfigTreeBuilder()->getRootNode())
            ->end();

        return $builder;
    }
}
