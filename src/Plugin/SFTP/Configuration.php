<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP;

use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;
use Kiboko\Contract\Configurator\PluginConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements PluginConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('sftp');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('loader')
                    ->children()
                        ->arrayNode('servers')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('host')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->integerNode('port')
                                        ->min(1)
                                        ->max(65535)
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('username')
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('password')
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('public_key')
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('private_key')
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('private_key_passphrase')
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('base_path')
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('put')
                            ->isRequired()
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('path')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('content')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('mode')
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                    ->scalarNode('if')
                                        ->validate()
                                            ->ifTrue(isExpression())
                                            ->then(asExpression())
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
