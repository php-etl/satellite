<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering;

use Kiboko\Contract\Configurator\PluginConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

use function Kiboko\Component\SatelliteToolbox\Configuration\mutuallyExclusiveFields;

final class Configuration implements PluginConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $reject = new Configuration\Reject();
        $drop = new Configuration\Drop();

        $builder = new TreeBuilder('filter');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->validate()
                ->always($this->cleanupFields('reject', 'drop'))
            ->end()
            ->validate()
                ->always(mutuallyExclusiveFields('reject', 'drop'))
            ->end()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->append(node: $reject->getConfigTreeBuilder()->getRootNode())
                ->append(node: $drop->getConfigTreeBuilder()->getRootNode())
            ->end()
        ;

        return $builder;
    }

    private function cleanupFields(string ...$fieldNames): \Closure
    {
        return function (array $value) use ($fieldNames) {
            foreach ($fieldNames as $fieldName) {
                if (!\array_key_exists($fieldName, $value)) {
                    continue;
                }

                if (!\is_array($value[$fieldName]) || \count($value[$fieldName]) <= 0) {
                    unset($value[$fieldName]);
                }
            }

            return $value;
        };
    }
}
