<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream;

use Kiboko\Component\Satellite\NamedConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\ExpressionLanguage\Expression;

final class Configuration implements ConfigurationInterface, NamedConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('loader')
                    ->children()
                        ->scalarNode('destination')
                            ->validate()
                                ->ifTrue(fn ($value) => in_array($value, ['stderr', 'stdout']))
                                ->then(fn ($value) => sprintf('php://%s', $value))
                            ->end()
                            ->setDeprecated()
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(fn ($data) => is_string($data) && $data !== '' && str_starts_with($data, '@='))
                                ->then(fn ($data) => new Expression(substr($data, 2)))
                            ->end()
                        ->end()
                        ->enumNode('format')
                            ->values(['json', 'debug'])
                            ->cannotBeEmpty()
                            ->validate()
                                ->ifTrue(fn ($data) => is_string($data) && $data !== '' && str_starts_with($data, '@='))
                                ->then(fn ($data) => new Expression(substr($data, 2)))
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }

    public function getName(): string
    {
        return 'stream';
    }
}
