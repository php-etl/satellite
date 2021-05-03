<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Configuration;

use Symfony\Component\Config;

final class RabbitMQConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('rabbitmq');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('servers')
                    ->fixXmlConfig('server')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->ignoreExtraKeys()
                    ->isRequired()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('host')->isRequired()->end()
                            ->scalarNode('port')->isRequired()->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('vhost')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('channels')
                    ->fixXmlConfig('channel')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->ignoreExtraKeys()
                    ->isRequired()
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $builder;
    }
}
