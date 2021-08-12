<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Kiboko\Component\Satellite\NamedConfigurationInterface;
use Symfony\Component\Config;

final class Configuration implements Config\Definition\ConfigurationInterface, NamedConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder($this->getName());

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

    public function getName(): string
    {
        return 'state';
    }
}
