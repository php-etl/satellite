<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Api;

use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Satellite\Runtime\RuntimeConfigurationInterface
{
    /** @var array<string, Satellite\Plugin\PluginConfigurationInterface> */
    private iterable $plugins = [];
    /** @var array<string, Satellite\Feature\FeatureConfigurationInterface> */
    private iterable $features = [];

    public function addPlugin(string $name, Satellite\Plugin\PluginConfigurationInterface $plugin): self
    {
        $this->plugins[$name] = $plugin;

        return $this;
    }

    public function addFeature(string $name, Satellite\Feature\FeatureConfigurationInterface $feature): self
    {
        $this->features[$name] = $feature;

        return $this;
    }

    public function getStepsTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('steps');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->requiresAtLeastOneElement()
            ->cannotBeEmpty()
            ->isRequired()
            ->fixXmlConfig('step')
            ->validate()
                ->ifTrue(function ($value) {
                    return 1 <= array_reduce(
                        array_keys($this->plugins),
                        fn (int $count, string $plugin)
                            => array_key_exists($plugin, $value) ? $count + 1 : $count,
                        0
                    );
                })
                ->thenInvalid(sprintf('You should only specify one plugin between %s.', implode('", "', array_map(fn (string $plugin) => sprintf('"%s"', $plugin), array_keys($this->plugins)))))
            ->end();

        $node = $builder->getRootNode()->arrayPrototype();

        $this
            ->applyPlugins($node)
            ->applyFeatures($node);

        return $builder;
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('http_api');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('name')->end()
                ->scalarNode('path')->end()
                ->arrayNode('routes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('route')->end()
                            ->append($this->getStepsTreeBuilder()->getRootNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    private function applyPlugins(ArrayNodeDefinition $node): self
    {
        foreach ($this->plugins as $plugin) {
            $node->append($plugin->getConfigTreeBuilder()->getRootNode());
        }

        return $this;
    }

    private function applyFeatures(ArrayNodeDefinition $node): self
    {
        foreach ($this->features as $feature) {
            $node->append($feature->getConfigTreeBuilder()->getRootNode());
        }

        return $this;
    }
}
