<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Kiboko\Contract\Configurator\Feature;
use Symfony\Component\Config;

#[Feature(name: "state")]
final class Configuration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('state');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('destinations')
                    ->fixXmlConfig('destination')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->ignoreExtraKeys()
                    ->arrayPrototype()
                        ->children()
                            ->append((new Configuration\RedisConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\MemcachedConfiguration())->getConfigTreeBuilder()->getRootNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
