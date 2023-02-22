<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Configuration;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\mutuallyExclusiveFields;

final class BackwardCompatibilityConfiguration implements ConfigurationInterface
{
    /** @var array<string, Configurator\AdapterConfigurationInterface> */
    private array $adapters = [];
    /** @var array<string, Configurator\RuntimeConfigurationInterface> */
    private array $runtimes = [];

    public function addAdapter(string $name, Configurator\AdapterConfigurationInterface $adapter): self
    {
        $this->adapters[$name] = $adapter;

        return $this;
    }

    public function addRuntime(string $name, Configurator\RuntimeConfigurationInterface $runtime): self
    {
        $this->runtimes[$name] = $runtime;

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('satellite');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->beforeNormalization()
                ->always(mutuallyExclusiveFields(...array_keys($this->adapters)))
            ->end()
            ->beforeNormalization()
                ->always(mutuallyExclusiveFields(...array_keys($this->runtimes)))
            ->end()
            ->children()
                ->append((new Satellite\Feature\Composer\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->end()
        ;

        $root = $builder->getRootNode();

        if (!$root instanceof ArrayNodeDefinition) {
            throw new \RuntimeException(strtr('Expected an instance of %expected%, but got %actual%.', ['%expected%' => ArrayNodeDefinition::class, '%actual%' => get_debug_type($root)]));
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
}
