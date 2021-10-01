<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Configuration;

use Kiboko\Component\Satellite\DependencyInjection\Configuration\ServicesConfiguration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\ExpressionLanguage\Expression;

final class Extractor implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('extractor');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->append((new ServicesConfiguration())->getConfigTreeBuilder()->getRootNode())
                ->scalarNode('use')->end()
                ->arrayNode('parameters')
                    ->useAttributeAsKey('keyparam')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(fn ($data) => is_string($data) && $data !== '' && str_starts_with($data, '@='))
                            ->then(fn ($data) => new Expression(substr($data, 2)))
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
