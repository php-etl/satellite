<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('stream');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('loader')
                    ->children()
                        ->enumNode('destination')->values(['stdout', 'stderr'])->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
