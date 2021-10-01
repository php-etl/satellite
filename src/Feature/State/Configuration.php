<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Symfony\Component\Config;

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
                        ->beforeNormalization()
                            ->always(function ($data) {
                                $config = [];
                                if (is_string($data) && $data !== '' && str_starts_with($data, '@')) {
                                    $config['service']['use'] = substr($data, 1);
                                }

                                return $config;
                            })
                        ->end()
                        ->children()
                            ->append((new Configuration\ServiceConfiguration())->getConfigTreeBuilder()->getRootNode())
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
