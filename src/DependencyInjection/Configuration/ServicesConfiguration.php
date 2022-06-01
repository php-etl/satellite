<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\DependencyInjection\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ServicesConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('services');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->beforeNormalization()
                ->always(function ($data) {
                    foreach ($data as $identifier => &$service) {
                        if (null === $service) {
                            $service['class'] = $identifier;
                        } else {
                            if (!\array_key_exists('class', $service)) {
                                $service['class'] = $identifier;
                            }
                        }
                    }

                    return $data;
                })
            ->end()
            ->beforeNormalization()
                ->always(function ($data) {
                    foreach ($data as &$service) {
                        if (\array_key_exists('calls', $service)) {
                            $service['calls'] = array_merge(...$service['calls']);
                        }
                    }

                    return $data;
                })
            ->end()
            ->arrayPrototype()
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->arrayNode('arguments')
    //                        ->useAttributeAsKey('key')
                        ->variablePrototype()->end()
                    ->end()
                    ->arrayNode('calls')
                        ->useAttributeAsKey('key')
                        ->arrayPrototype()
                            ->variablePrototype()->end()
                        ->end()
                    ->end()
                    ->booleanNode('public')
                        ->defaultFalse()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
