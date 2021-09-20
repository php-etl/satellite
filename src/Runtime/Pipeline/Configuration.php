<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $builder = new TreeBuilder('pipeline');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('name')->end()
                ->arrayNode('steps')
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
                    ->end()
                ->end()
            ->end();

        $node = $builder->getRootNode()->find('steps')->arrayPrototype();

        $this
            ->applyPlugins($node)
            ->applyFeatures($node);

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
