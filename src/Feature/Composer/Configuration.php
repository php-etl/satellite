<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Composer;

use Kiboko\Contract\Configurator\FeatureConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use function Kiboko\Component\SatelliteToolbox\Configuration\mutuallyExclusiveFields;

final class Configuration implements FeatureConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('composer');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->beforeNormalization()
                ->ifTrue(fn ($data) => array_key_exists('from_local', $data) && $data['from_local'] === true)
                ->then(mutuallyExclusiveFields('from_local', 'autoload', 'repositories', 'require'))
            ->end()
            ->children()
                ->booleanNode('from_local')->defaultFalse()->end()
                ->arrayNode('autoload')
                    ->children()
                        ->arrayNode('psr4')
                            ->useAttributeAsKey('namespace')
                            ->arrayPrototype()
                                ->beforeNormalization()
                                    ->always(function ($data) {
                                        if (array_key_exists('path', $data) && array_key_exists('paths', $data)) {
                                            throw new InvalidConfigurationException('You should either specify the "path" or the "paths" options.');
                                        }

                                        if (array_key_exists('paths', $data)) {
                                            return $data;
                                        }

                                        $data['paths'] = [$data['path']];
                                        unset($data['path']);

                                        return $data;
                                    })
                                ->end()
                                ->children()
                                    ->scalarNode('namespace')->end()
                                    ->scalarNode('path')->end()
                                    ->arrayNode('paths')
                                        ->beforeNormalization()->castToArray()->end()
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('repositories')
                    ->arrayPrototype()
                        ->children()
                            ->enumNode('type')
                                ->isRequired()
                                ->values(['vcs', 'git', 'git-bitbucket', 'hg-bitbucket', 'github', 'perforce', 'fossil', 'svn', 'hg'])
                            ->end()
                            ->scalarNode('url')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('require')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $builder;
    }
}
