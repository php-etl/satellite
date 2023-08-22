<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Api;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final readonly class Configuration implements Configurator\RuntimeConfigurationInterface
{
    private Satellite\Runtime\Pipeline\Configuration $pipelineConfiguration;
    private Satellite\Runtime\Workflow\Action\Configuration $actionConfiguration;

    public function __construct()
    {
        $this->pipelineConfiguration = new Satellite\Runtime\Pipeline\Configuration();
        $this->actionConfiguration = new Satellite\Runtime\Workflow\Action\Configuration();
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

    public function addAction(string $name, Configurator\ActionConfigurationInterface $action): self
    {
        $this->actionConfiguration->addAction(
            $name,
            $action
        );

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('http_api');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('name')->end()
                ->scalarNode('path')->isRequired()->end()
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
                ->arrayNode('routes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('route')->isRequired()->end()
                            ->scalarNode('method')->defaultValue('post')->end()
                            ->scalarNode('expression')->isRequired()->end()
                            ->append($this->pipelineConfiguration->getConfigTreeBuilder()->getRootNode())
                            ->append($this->actionConfiguration->getConfigTreeBuilder()->getRootNode())
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
