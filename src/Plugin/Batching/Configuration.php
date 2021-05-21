<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\ExpressionLanguage\Expression;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('batch');

        $builder->getRootNode()
            ->children()
                ->arrayNode('merge')
                    ->children()
                        ->integerNode('size')
                            ->validate()
                                ->ifTrue(fn ($data) => is_string($data) && $data !== '' && str_starts_with($data, '@='))
                                ->then(fn ($data) => new Expression(substr($data, 2)))
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
