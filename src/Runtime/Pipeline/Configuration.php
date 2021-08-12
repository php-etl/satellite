<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator\FactoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Satellite\NamedConfigurationInterface, Satellite\Runtime\ConfigTreePluginInterface
{
    /** @var array<FactoryInterface> $plugins */
    private array $plugins = [];

    public function getName(): string
    {
        return 'pipeline';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('steps')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->isRequired()
                    ->fixXmlConfig('step')
                    ->validate()
                        ->ifTrue(function ($value) {
                            $keys = [];
                            foreach ($value as $item) {
                                $keys = array_merge($keys, array_intersect($this->getPluginsKeys(), array_keys($item)));
                            }

                            return 1 <= count(
                                    array_filter(array_count_values($keys), function ($value) {
                                        return $value > 1;
                                    })
                                );
                        })
                        ->thenInvalid(sprintf('You should only specify one plugin between "%s".', implode('","', $this->getPluginsKeys())))
                    ->end()
                ->end()
            ->end();

        $stepsPrototype = $builder->getRootNode()->find('steps')->arrayPrototype();
        foreach ($this->getPluginsConfiguration() as $pluginConfig) {
            $stepsPrototype->append($pluginConfig->getConfigTreeBuilder()->getRootNode());
        }

        // Flow features
        $stepsPrototype
            ->append((new Satellite\Feature\Logger\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->append((new Satellite\Feature\State\Configuration())->getConfigTreeBuilder()->getRootNode())
            ->append((new Satellite\Feature\Rejection\Configuration())->getConfigTreeBuilder()->getRootNode())
        ->end();

        return $builder;
    }

    public function addPlugins(FactoryInterface ...$plugins): self
    {
        array_push($this->plugins, ...$plugins);

        return $this;
    }

    private function getPluginsConfiguration(): array
    {
        $plugins = array_filter($this->plugins, function ($plugin) {
            return $plugin instanceof FactoryInterface
                && $plugin->configuration() instanceof Satellite\NamedConfigurationInterface;
        });

        /** @var FactoryInterface $plugin */
        foreach ($plugins as $plugin) {
            $result[$plugin->configuration()->getName()] = $plugin->configuration();
        }

        return $result ?? [];
    }

    private function getPluginsKeys(): array
    {
        return array_keys($this->getPluginsConfiguration());
    }
}
