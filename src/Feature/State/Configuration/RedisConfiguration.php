<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Configuration;

use Symfony\Component\Config;

final class RedisConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('redis');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
            ->arrayNode('servers')
            ->fixXmlConfig('server')
            ->requiresAtLeastOneElement()
            ->cannotBeEmpty()
            ->ignoreExtraKeys()
            ->isRequired()
            ->arrayPrototype()
            ->validate()
            ->ifTrue(function ($value) {
                return \array_key_exists('socket', $value)
                                    && \array_key_exists('host', $value);
            })
            ->thenInvalid('Options "socket" and "host" are mutually exclusive, you should declare one or another, not both.')
            ->end()
            ->validate()
            ->ifTrue(function ($value) {
                return !\array_key_exists('socket', $value)
                                    && !\array_key_exists('host', $value);
            })
            ->thenInvalid('You should either declare the "socket" or the "host" options.')
            ->end()
            ->validate()
            ->ifTrue(function ($value) {
                return \array_key_exists('host', $value)
                                    && !\array_key_exists('port', $value);
            })
            ->thenInvalid('Options "host" and "port" should be both declared.')
            ->end()
            ->children()
            ->scalarNode('socket')->end()
            ->scalarNode('host')->end()
            ->scalarNode('port')->end()
            ->scalarNode('user')->end()
            ->scalarNode('password')->end()
            ->scalarNode('dbindex')->isRequired()->end()
            ->integerNode('timeout')->isRequired()->end()
            ->integerNode('retry_interval')->end()
            ->integerNode('read_timeout')->end()
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $builder;
    }
}
