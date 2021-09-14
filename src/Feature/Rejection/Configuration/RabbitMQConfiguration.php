<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Configuration;

use Symfony\Component\Config;
use function Kiboko\Component\SatelliteToolbox\Configuration\asExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\isExpression;

final class RabbitMQConfiguration implements Config\Definition\ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new Config\Definition\Builder\TreeBuilder('rabbitmq');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->variableNode('host')
                    ->isRequired()
                ->end()
                ->variableNode('port')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                    ->isRequired()
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
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                    ->isRequired()
                ->end()
                ->variableNode('exchange')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                ->end()
                ->variableNode('topic')
                    ->validate()
                        ->ifTrue(isExpression())
                        ->then(asExpression())
                    ->end()
                    ->isRequired()
                ->end()
            ->end();

        return $builder;
    }
}
