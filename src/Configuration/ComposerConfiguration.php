<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ComposerConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('composer');

        $builder->getRootNode()
            ->children()
                ->booleanNode('from-local')->defaultFalse()->end()
                ->arrayNode('autoload')
                    ->children()
                        ->arrayNode('psr4')
                            ->useAttributeAsKey('namespace')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('namespace')->end()
                                    ->arrayNode('path')
                                        ->beforeNormalization()->castToArray()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
