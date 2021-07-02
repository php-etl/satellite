<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ServicesConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('services');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->beforeNormalization()
                ->always(function ($data) {
                    foreach ($data as $identifier => &$service) {
                        if (!array_key_exists('class', $service)) {
                            $service['class'] = $identifier;
                        }
                    }
                    return $data;
                })
            ->end()
            ->arrayPrototype()
                ->children()
                    ->scalarNode('class')->isRequired()->end()
                    ->arrayNode('arguments')
                        ->useAttributeAsKey('key')
                        ->scalarPrototype()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
