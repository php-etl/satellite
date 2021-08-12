<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP;

use Kiboko\Component\Satellite\NamedConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class Configuration implements ConfigurationInterface, NamedConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
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
            ->end();

        return $builder;
    }

    public function getName(): string
    {
        return 'sftp';
    }
}
