<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Api;

use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\Runtime\ConfigTreePluginInterface;
use Kiboko\Contract\Configurator\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Satellite\NamedConfigurationInterface, ConfigTreePluginInterface
{
    /** @var array<FactoryInterface> $plugins */
    private array $plugins = [];

    public function getName(): string
    {
        return 'http_api';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
                ->arrayNode('routes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('route')->end()
                            ->append((new Satellite\Runtime\Pipeline\Configuration())->addPlugins(...$this->plugins)->getConfigTreeBuilder()->getRootNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    public function addPlugins(FactoryInterface ...$plugins): self
    {
        array_push($this->plugins, ...$plugins);

        return $this;
    }
}
