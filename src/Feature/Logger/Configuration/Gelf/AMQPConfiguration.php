<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Configuration\Gelf;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class AMQPConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('amqp');

        $builder->getRootNode()
            ->children()
                ->scalarNode('queue')->end()
                ->scalarNode('host')
                    ->info('The host to connect to. Note: Max 1024 characters.')
                ->end()
                ->scalarNode('port')
                    ->info('Port on the host.')
                ->end()
                ->scalarNode('vhost')
                    ->info('The virtual host on the host. Note: Max 128 characters.')
                ->end()
                ->scalarNode('login')
                    ->info('The login name to use. Note: Max 128 characters.')
                ->end()
                ->scalarNode('password')
                    ->info('Password. Note: Max 128 characters.')
                ->end()
                ->scalarNode('timeout')
                    ->info('Timeout in seconds for outcome activity. Note: 0 or greater seconds. May be fractional.')
                ->end()
                ->scalarNode('connection_timeout')
                    ->info('Connection timeout. Note: 0 or greater seconds. May be fractional.')
                ->end()
                ->arrayNode('tls')
                    ->children()
                        ->scalarNode('cacert')
                            ->info('Path to the CA cert file in PEM format.')
                        ->end()
                        ->scalarNode('cert')
                            ->info('Path to the client certificate in PEM foramt.')
                        ->end()
                        ->scalarNode('key')
                            ->info('Path to the client key in PEM format.')
                        ->end()
                        ->booleanNode('verify')
                            ->info('Enable or disable peer verification. If peer verification is enabled then the common name in the server certificate must match the server name. Peer verification is enabled by default.')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
