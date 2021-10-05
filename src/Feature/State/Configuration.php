<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Kiboko\Contract\Configurator\FeatureConfigurationInterface;
use Symfony\Component\Config;

final class Configuration implements FeatureConfigurationInterface
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
                            ->scalarNode('service')
                                ->beforeNormalization()
                                    ->always(function ($data) {
                                        if (is_string($data) && $data !== '' && str_starts_with($data, '@')) {
                                            $data = substr($data, 1);
                                        }

                                        return $data;
                                    })
                                ->end()
                            ->end()
                            ->append((new Configuration\RedisConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\MemcachedConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\RabbitMQConfiguration())->getConfigTreeBuilder()->getRootNode())
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
