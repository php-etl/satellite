<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action;

use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /** @var array<string, Configurator\PluginConfigurationInterface> */
    private iterable $plugins = [];

    public function addPlugin(string $name, Configurator\PluginConfigurationInterface $plugin): self
    {
        $this->plugins[$name] = $plugin;

        return $this;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('actions');
        $node = $builder->getRootNode();

        /* @phpstan-ignore-next-line */
        foreach ($this->plugins as $plugin) {
            /* @phpstan-ignore-next-line */
            $node ->append($plugin->getConfigTreeBuilder()->getRootNode());
        }

        return $builder;
    }
}
