<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Contract\Configurator\PluginConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements PluginConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $extractor = new Configuration\Extractor();
        $transformer = new Configuration\Transformer();
        $loader = new Configuration\Loader();

        $builder = new TreeBuilder('custom');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(fn (array $value) => \array_key_exists('extractor', $value) && \array_key_exists('loader', $value))
                ->thenInvalid('Your configuration should either contain the "extractor" or the "loader" key, not both.')
            ->end()
            ->validate()
                ->ifTrue(fn (array $value) => \array_key_exists('extractor', $value) && \array_key_exists('transformer', $value))
                ->thenInvalid('Your configuration should either contain the "extractor" or the "transformer" key, not both.')
            ->end()
            ->validate()
                ->ifTrue(fn (array $value) => \array_key_exists('loader', $value) && \array_key_exists('transformer', $value))
                ->thenInvalid('Your configuration should either contain the "loader" or the "transformer" key, not both.')
            ->end()
            ->beforeNormalization()
                ->always(function (array $value) {
                    if (\array_key_exists('expression_language', $value) && \count($value['expression_language']) <= 0)
                    {
                        unset($value['expression_language']);
                    }

                    return $value;
                })
            ->end()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->append(node: $extractor->getConfigTreeBuilder()->getRootNode())
                ->append(node: $loader->getConfigTreeBuilder()->getRootNode())
                ->append(node: $transformer->getConfigTreeBuilder()->getRootNode())
            ->end()
        ;

        return $builder;
    }
}
