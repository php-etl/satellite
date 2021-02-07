<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Extractor implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('extractor');

        $builder->getRootNode()
            ->children()
                ->scalarNode('class')
            ->end();

        return $builder;
    }
}
