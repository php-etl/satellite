<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger;

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
                ->scalarNode('channel')->end()
                ->enumNode('type')
                    ->values(['null', 'stderr'])
                    ->setDeprecated('php-etl/logger-plugin', '0.1.x-dev', 'This notation is deprecated and will be removed in am upcoming version, please use top-level notation instead.')
                ->end()
                ->arrayNode('destinations')
                    ->fixXmlConfig('destination')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->ignoreExtraKeys()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('blackhole')
                                ->validate()
                                    ->ifTrue(fn ($value) => $value !== null)
                                    ->thenInvalid('No value can be accepted in the blackhole logger, please set null.')
                                ->end()
                            ->end()
                            ->scalarNode('stderr')
                                ->validate()
                                    ->ifTrue(fn ($value) => $value !== null)
                                    ->thenInvalid('No value can be accepted in the stderr logger, please set null.')
                                ->end()
                            ->end()
                            ->append((new Configuration\StreamConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\SyslogConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\GelfConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\ElasticSearchConfiguration())->getConfigTreeBuilder()->getRootNode())
                            ->append((new Configuration\LogstashConfiguration())->getConfigTreeBuilder()->getRootNode())
//                            ->arrayNode('syslog_udp')->end()
//                            ->arrayNode('slack')->end()
//                            ->arrayNode('slack_webhook')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }

    public function getName(): string
    {
        return 'logger';
    }
}
