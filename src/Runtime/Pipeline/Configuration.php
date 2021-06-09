<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\Spreadsheet;
use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class Configuration implements Satellite\NamedConfigurationInterface
{
    public function getName(): string
    {
        return 'pipeline';
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder($this->getName());

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('steps')
                    ->requiresAtLeastOneElement()
                    ->cannotBeEmpty()
                    ->isRequired()
                    ->fixXmlConfig('step')
                    ->validate()
                        ->ifTrue(function ($value) {
                            return 1 <= count(array_filter([
                                array_key_exists('akeneo', $value),
                                array_key_exists('sylius', $value),
                                array_key_exists('csv', $value),
                                array_key_exists('spreadsheet', $value),
                                array_key_exists('fastmap', $value),
                                array_key_exists('api', $value),
                                array_key_exists('custom', $value),
                                array_key_exists('stream', $value),
                                array_key_exists('sftp', $value),
                                array_key_exists('batch', $value),
                            ]));
                        })
                        ->thenInvalid('You should only specify one plugin beetween "akeneo", "sylius", "csv", "spreadsheet", "fastmap", "api", "custom", "stream", "sftp" and "batch".')
                    ->end()
                    ->arrayPrototype()
                        // Plugins
                        ->append((new Akeneo\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Sylius\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new CSV\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Spreadsheet\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new FastMap\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Custom\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Stream\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\SFTP\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Batching\Configuration())->getConfigTreeBuilder()->getRootNode())
                        // Flow features
                        ->append((new Satellite\Feature\Logger\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Feature\State\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Feature\Rejection\Configuration())->getConfigTreeBuilder()->getRootNode())
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
