<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Configuration;

use Symfony\Component\Config;

final class ServiceConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('service');

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
