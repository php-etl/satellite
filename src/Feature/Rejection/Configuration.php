<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection;

use Kiboko\Contract\Configurator\FeatureConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements FeatureConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('rejection');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
            ->arrayNode('destinations')
            ->fixXmlConfig('destination')
            ->requiresAtLeastOneElement()
            ->cannotBeEmpty()
            ->ignoreExtraKeys()
            ->arrayPrototype()
            ->children()
            ->append((new Configuration\KafkaConfiguration())->getConfigTreeBuilder()->getRootNode())
            ->append((new Configuration\RabbitMQConfiguration())->getConfigTreeBuilder()->getRootNode())
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $builder;
    }
}
