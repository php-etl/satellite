<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class VersionConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('version');

        /* @phpstan-ignore-next-line */
        $treeBuilder->getRootNode()
            ->beforeNormalization()->castToArray()->end()
            ->enumPrototype()
                ->values(['0.3'])
            ->end()
        ;

        return $treeBuilder;
    }
}
