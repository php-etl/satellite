<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Configuration;

use Symfony\Component\Config;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class RabbitMQConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new Config\Definition\Builder\TreeBuilder('rabbitmq');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->variableNode('host')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('port')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('user')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('password')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('vhost')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('line_threshold')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('exchange')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('topic')
                    ->isRequired()
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
