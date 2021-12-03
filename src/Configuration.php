<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite\Configuration\BackwardCompatibilityConfiguration;
use Kiboko\Component\Satellite\Configuration\VersionConfiguration;
use Kiboko\Component\Satellite\Feature;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\mutuallyExclusiveFields;
use function Kiboko\Component\SatelliteToolbox\Configuration\mutuallyDependentFields;

final class Configuration implements ConfigurationInterface
{
    /** @var array<string, Configurator\AdapterConfigurationInterface> */
    private array $adapters = [];
    /** @var array<string, Configurator\RuntimeConfigurationInterface> */
    private array $runtimes = [];
    /** @var array<string, Configurator\PluginConfigurationInterface> */
    private array $plugins = [];
    /** @var array<string, Configurator\FeatureConfigurationInterface> */
    private array $features = [];
    private BackwardCompatibilityConfiguration $backwardCompatibilityConfiguration;

    public function __construct()
    {
        $this->backwardCompatibilityConfiguration = new BackwardCompatibilityConfiguration();
    }

    public function addAdapter(string $name, Configurator\AdapterConfigurationInterface $adapter): self
    {
        $this->adapters[$name] = $adapter;
        $this->backwardCompatibilityConfiguration->addAdapter($name, $adapter);

        return $this;
    }

    public function addRuntime(string $name, Configurator\RuntimeConfigurationInterface $runtime): self
    {
        $this->runtimes[$name] = $runtime;
        $this->backwardCompatibilityConfiguration->addRuntime($name, $runtime);

        foreach ($this->features as $name => $feature) {
            $runtime->addFeature($name, $feature);
        }
        foreach ($this->plugins as $name => $plugin) {
            $runtime->addPlugin($name, $plugin);
        }

        return $this;
    }

    public function addPlugin(string $name, Configurator\PluginConfigurationInterface $plugin): self
    {
        $this->plugins[$name] = $plugin;
        $this->backwardCompatibilityConfiguration->addPlugin($name, $plugin);

        foreach ($this->runtimes as $runtime) {
            $runtime->addPlugin($name, $plugin);
        }

        return $this;
    }

    public function addFeature(string $name, Configurator\FeatureConfigurationInterface $feature): self
    {
        $this->features[$name] = $feature;
        $this->backwardCompatibilityConfiguration->addFeature($name, $feature);

        foreach ($this->runtimes as $runtime) {
            $runtime->addFeature($name, $feature);
        }

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('etl');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->append((new VersionConfiguration())->getConfigTreeBuilder()->getRootNode())
            ->append($this->backwardCompatibilityConfiguration->getConfigTreeBuilder()->getRootNode())
            ->beforeNormalization()
                ->always(mutuallyDependentFields('satellites', 'version'))
            ->end()
            ->beforeNormalization()
                ->always(mutuallyExclusiveFields('satellite', 'version'))
            ->end()
            ->validate()
                ->ifTrue(fn ($data) => array_key_exists('satellites', $data) && is_array($data['satellites']) && count($data['satellites']) <= 0)
                ->then(function ($data) {
                    unset($data['satellites']);
                    return $data;
                })
            ->end()
            ->validate()
                ->ifTrue(fn ($data) => array_key_exists('version', $data) && is_array($data['version']) && count($data['version']) <= 0)
                ->then(function ($data) {
                    unset($data['version']);
                    return $data;
                })
            ->end()
            ->children()
                ->arrayNode('satellites')->end()
            ->end();

        $root = $builder->getRootNode();

        if (!$root instanceof ArrayNodeDefinition) {
            throw new \RuntimeException(strtr(
                'Expected an instance of %expected%, but got %actual%.',
                [
                    '%expected%' => ArrayNodeDefinition::class,
                    '%actual%' => get_debug_type($root),
                ]
            ));
        }

        $children = $root->find('satellites');

        if (!$children instanceof ArrayNodeDefinition) {
            throw new \RuntimeException(strtr(
                'Expected an instance of %expected%, but got %actual%.',
                [
                    '%expected%' => ArrayNodeDefinition::class,
                    '%actual%' => get_debug_type($root),
                ]
            ));
        }

        $this->buildSatelliteTree($children->arrayPrototype());

        return $builder;
    }

    private function buildSatelliteTree(ArrayNodeDefinition $node): void
    {
        /** @phpstan-ignore-next-line */
        $node
            ->beforeNormalization()
                ->always(mutuallyExclusiveFields(...array_keys($this->adapters)))
            ->end()
            ->beforeNormalization()
                ->always(mutuallyExclusiveFields(...array_keys($this->runtimes)))
            ->end()
            ->children()
                ->scalarNode('label')
                    ->isRequired()
                ->end()
                ->append((new Feature\Composer\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->end();

        foreach ($this->adapters as $config) {
            $node->append($config->getConfigTreeBuilder()->getRootNode());
        }

        foreach ($this->runtimes as $config) {
            $node->append($config->getConfigTreeBuilder()->getRootNode());
        }
    }
}
