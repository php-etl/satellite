<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Configuration;

use Symfony\Component\Config;

final class KafkaConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('kafka');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('brokers')
                    ->fixXmlConfig('broker')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->ignoreExtraKeys()
                    ->isRequired()
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('topics')
                    ->fixXmlConfig('topic')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->ignoreExtraKeys()
                    ->isRequired()
                    ->scalarPrototype()->end()
                ->end()
                ->booleanNode('auto_commit')->end()
                ->scalarNode('group')->end()
            ->end();

        return $builder;
    }
}
