<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Component\Satellite\DependencyInjection\Configuration\ServicesConfiguration;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Configurator\RuntimeConfigurationInterface
{
    /** @var array<string, Configurator\PluginConfigurationInterface> */
    private iterable $plugins = [];
    /** @var array<string, Configurator\FeatureConfigurationInterface> */
    private iterable $features = [];

    public function addPlugin(string $name, Configurator\PluginConfigurationInterface $plugin): self
    {
        $this->plugins[$name] = $plugin;

        return $this;
    }

    public function addFeature(string $name, Configurator\FeatureConfigurationInterface $feature): self
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

        $node
            ->children()
                ->scalarNode('name')->end()
            ->end();

        $this
            ->applyPlugins($node)
            ->applyFeatures($node);

        return $builder;
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('pipeline');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->append((new ServicesConfiguration())->getConfigTreeBuilder()->getRootNode())
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('name')->end()
                ->append($this->getStepsTreeBuilder()->getRootNode())
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
