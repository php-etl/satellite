<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite\Configuration\BackwardCompatibilityConfiguration;
use Kiboko\Component\Satellite\Configuration\VersionConfiguration;
use Kiboko\Component\Satellite\Feature;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Kiboko\Component\SatelliteToolbox;

final class Configuration implements ConfigurationInterface
{
    /** @var iterable<NamedConfigurationInterface> */
    private iterable $adapters;
    /** @var iterable<NamedConfigurationInterface> */
    private iterable $runtimes;
    private BackwardCompatibilityConfiguration $backwardCompatibilityConfiguration;

    public function __construct()
    {
        $this->adapters = [];
        $this->runtimes = [];
        $this->backwardCompatibilityConfiguration = new BackwardCompatibilityConfiguration();
    }

    public function addAdapters(NamedConfigurationInterface ...$adapters): self
    {
        array_push($this->adapters, ...$adapters);
        $this->backwardCompatibilityConfiguration->addAdapters(...$adapters);

        return $this;
    }

    public function addRuntimes(NamedConfigurationInterface ...$runtimes): self
    {
        array_push($this->runtimes, ...$runtimes);
        $this->backwardCompatibilityConfiguration->addRuntimes(...$runtimes);

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('etl');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->append((new VersionConfiguration())->getConfigTreeBuilder()->getRootNode())
            ->append((new SatelliteToolbox\Configuration\ImportConfiguration())->getConfigTreeBuilder()->getRootNode())
            ->append($this->backwardCompatibilityConfiguration->getConfigTreeBuilder()->getRootNode())
            ->beforeNormalization()
                ->always($this->mutuallyDependentFields('satellites', 'version'))
            ->end()
            ->beforeNormalization()
                ->always($this->mutuallyExclusiveFields('satellite', 'version'))
            ->end()
            ->children()
                ->arrayNode('satellites')
                    ->append((new SatelliteToolbox\Configuration\ImportConfiguration())->getConfigTreeBuilder()->getRootNode())
                ->end()
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
        $node
            ->beforeNormalization()
                ->always($this->mutuallyExclusiveFields(...array_map(
                    fn (NamedConfigurationInterface $config) => $config->getName(),
                    $this->adapters
                )))
            ->end()
            ->beforeNormalization()
                ->always($this->mutuallyExclusiveFields(...array_map(
                    fn (NamedConfigurationInterface $config) => $config->getName(),
                    $this->runtimes
                )))
            ->end()
            ->validate()
                ->ifTrue(fn ($data) => array_key_exists('imports', $data) && is_array($data['imports']) && count($data['imports']) <= 0)
                ->then(function ($data) {
                    unset($data['imports']);
                    return $data;
                })
            ->end()
            ->children()
                ->scalarNode('label')
                    ->isRequired()
                ->end()
                ->append((new SatelliteToolbox\Configuration\ImportConfiguration())->getConfigTreeBuilder()->getRootNode())->end()
                ->append((new Feature\Composer\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->end();

        foreach ($this->adapters as $config) {
            $node->append($config->getConfigTreeBuilder()->getRootNode());
        }

        foreach ($this->runtimes as $config) {
            $node->append($config->getConfigTreeBuilder()->getRootNode());
        }
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
