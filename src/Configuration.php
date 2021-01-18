<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Flow\Akeneo;
use Kiboko\Component\Flow\FastMap;
use Kiboko\Plugin\CSV;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('satellite');

        $builder->getRootNode()
            ->children()
                ->append((new Configuration\ComposerConfiguration())->getConfigTreeBuilder()->getRootNode())
                ->scalarNode('image')->end()
                ->arrayNode('runtime')
                    ->children()
                        ->scalarNode('type')->isRequired()->end()
                        ->arrayNode('steps')
                            ->isRequired()
                            ->arrayPrototype()
                                ->append((new Akeneo\Configuration())->getConfigTreeBuilder()->getRootNode())
                                ->append((new CSV\Configuration())->getConfigTreeBuilder()->getRootNode())
                                ->append((new FastMap\Configuration())->getConfigTreeBuilder()->getRootNode())
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
