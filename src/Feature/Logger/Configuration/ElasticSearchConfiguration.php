<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Configuration;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ElasticSearchConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('elasticsearch');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
            ->enumNode('level')
            ->info('The minimum logging level at which this handler will be triggered')
            ->values([
                LogLevel::DEBUG,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
                LogLevel::ERROR,
                LogLevel::CRITICAL,
                LogLevel::ALERT,
                LogLevel::EMERGENCY,
            ])
            ->end()
            ->arrayNode('hosts')
            ->scalarPrototype()->example('http://user:password@localhost:9200')
            ->validate()
            ->ifTrue(isExpression())
            ->then(asExpression())
            ->end()
            ->end()
            ->end()
            ->scalarNode('elastic_cloud_id')->end()
            ->scalarNode('api_id')->end()
            ->scalarNode('api_key')->end()
//                ->arrayNode('tls')
//                    ->children()
//                        ->scalarNode('cacert')
//                            ->info('Path to the CA cert file in PEM format.')
//                        ->end()
//                        ->scalarNode('cert')
//                            ->info('Path to the client certificate in PEM foramt.')
//                        ->end()
//                        ->scalarNode('key')
//                            ->info('Path to the client key in PEM format.')
//                        ->end()
//                        ->booleanNode('verify')
//                            ->info('Enable or disable peer verification. If peer verification is enabled then the common name in the server certificate must match the server name. Peer verification is enabled by default.')
//                        ->end()
//                    ->end()
//                ->end()
            ->end()
        ;

        return $builder;
    }
}
