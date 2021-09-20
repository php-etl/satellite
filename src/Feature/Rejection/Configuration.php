<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection;

use Kiboko\Contract\Configurator\Feature;
use Symfony\Component\Config;

#[Feature(name: "rejection")]
final class Configuration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('rejection');

        /** @phpstan-ignore-next-line */
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
            ->end();

        return $builder;
    }
}
