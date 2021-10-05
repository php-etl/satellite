<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class CustomConfiguration implements ConfigurationInterface
{
    public function __construct(
        private string $treeBuilderName
    )
    {
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder($this->treeBuilderName);

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('use')
                    ->isRequired()
                ->end()
            ->end();

        return $builder;
    }
}
