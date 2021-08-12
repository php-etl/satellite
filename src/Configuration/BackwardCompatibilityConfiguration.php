<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Configuration;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class BackwardCompatibilityConfiguration implements ConfigurationInterface
{
    /** @var iterable<Satellite\NamedConfigurationInterface> */
    private iterable $adapters;
    /** @var iterable<Satellite\NamedConfigurationInterface> */
    private iterable $runtimes;
    /** @var iterable<FactoryInterface> */
    private iterable $plugins;

    public function __construct()
    {
        $this->adapters = [];
        $this->runtimes = [];
        $this->plugins = [];
    }

    public function addAdapters(Satellite\NamedConfigurationInterface ...$adapters): self
    {
        array_push($this->adapters, ...$adapters);

        return $this;
    }

    public function addRuntimes(Satellite\NamedConfigurationInterface ...$runtimes): self
    {
        array_push($this->runtimes, ...$runtimes);

        return $this;
    }

    public function addPlugins(FactoryInterface ...$plugins): self
    {
        array_push($this->plugins, ...$plugins);

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('satellite');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->append((new Satellite\Feature\Composer\Configuration())->getConfigTreeBuilder()->getRootNode())
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
        $children = $root->children();

        foreach ($this->adapters as $adapter) {
            $children->append($adapter->getConfigTreeBuilder()->getRootNode());
        }

        foreach ($this->runtimes as $runtime) {
            if ($runtime instanceof Satellite\Runtime\ConfigTreePluginInterface) {
                $runtime->addPlugins(...$this->plugins);
            }
            $children->append($runtime->getConfigTreeBuilder()->getRootNode());
        }

        return $builder;
    }

    private function getSatelliteTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('satellite');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->beforeNormalization()
                ->always($this->mutuallyExclusiveFields(...array_map(
                    fn (Satellite\NamedConfigurationInterface $config) => $config->getName(),
                    $this->adapters
                )))
            ->end()
            ->beforeNormalization()
                ->always($this->mutuallyExclusiveFields(...array_map(
                    fn (Satellite\NamedConfigurationInterface $config) => $config->getName(),
                    $this->runtimes
                )))
            ->end()
            ->children()
                ->append((new Satellite\Feature\Composer\Configuration())->getConfigTreeBuilder()->getRootNode())
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
        $children = $root->children();

        foreach ($this->adapters as $config) {
            $children->append($config->getConfigTreeBuilder()->getRootNode());
        }

        foreach ($this->runtimes as $config) {
            $children->append($config->getConfigTreeBuilder()->getRootNode());
        }

        return $builder;
    }

    private function mutuallyExclusiveFields(string ...$exclusions): \Closure
    {
        return function (array $value) use ($exclusions) {
            $fields = [];
            foreach ($exclusions as $exclusion) {
                if (array_key_exists($exclusion, $value)) {
                    $fields[] = $exclusion;
                }

                if (count($fields) < 2) {
                    continue;
                }

                throw new \InvalidArgumentException(sprintf(
                    'Your configuration should either contain the "%s" or the "%s" field, not both.',
                    ...$fields,
                ));
            }

            return $value;
        };
    }

    private function mutuallyDependentFields(string $field, string ...$dependencies): \Closure
    {
        return function (array $value) use ($field, $dependencies) {
            if (!array_key_exists($field, $value)) {
                return $value;
            }

            foreach ($dependencies as $dependency) {
                if (!array_key_exists($dependency, $value)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Your configuration should contain the "%s" field if the "%s" field is present.',
                        $dependency,
                        $field,
                    ));
                }
            }

            return $value;
        };
    }
}
