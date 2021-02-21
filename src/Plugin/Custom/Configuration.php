<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $extractor = new Configuration\Extractor();
        $transformer = new Configuration\Transformer();
        $loader = new Configuration\Loader();

        $builder = new TreeBuilder('custom');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->ifTrue(function (array $value) {
                    return array_key_exists('extractor', $value) && array_key_exists('loader', $value);
                })
                ->thenInvalid('Your configuration should either contain the "extractor" or the "loader" key, not both.')
            ->end()
            ->validate()
                ->ifTrue(function (array $value) {
                    return array_key_exists('extractor', $value) && array_key_exists('transformer', $value);
                })
                ->thenInvalid('Your configuration should either contain the "extractor" or the "transformer" key, not both.')
            ->end()
            ->validate()
                ->ifTrue(function (array $value) {
                    return array_key_exists('loader', $value) && array_key_exists('transformer', $value);
                })
                ->thenInvalid('Your configuration should either contain the "loader" or the "transformer" key, not both.')
            ->end()
            ->children()
                ->append(node: $extractor->getConfigTreeBuilder()->getRootNode())
                ->append(node: $loader->getConfigTreeBuilder()->getRootNode())
                ->append(node: $transformer->getConfigTreeBuilder()->getRootNode())
            ->end()
        ;

        return $builder;
    }
}
