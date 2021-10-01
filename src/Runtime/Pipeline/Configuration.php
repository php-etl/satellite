<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Component\Satellite\DependencyInjection\Configuration\ServicesConfiguration;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\SQL;
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
                ->append((new ServicesConfiguration())->getConfigTreeBuilder()->getRootNode())
                ->arrayNode('expression_language')
                    ->scalarPrototype()->end()
                ->end()
                ->scalarNode('name')->end()
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
                                array_key_exists('ftp', $value),
                                array_key_exists('batch', $value),
                                array_key_exists('sql', $value),
                            ]));
                        })
                        ->thenInvalid('You should only specify one plugin between "akeneo", "sylius", "csv", "spreadsheet", "fastmap", "api", "custom", "stream", "sftp", "ftp", "sql" and "batch".')
                    ->end()
                    ->arrayPrototype()
                        // Name of the step
                        ->children()
                            ->scalarNode('name')->end()
                        ->end()
                        // Plugins
                        ->append((new Akeneo\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Sylius\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new CSV\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new SQL\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Spreadsheet\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new FastMap\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Custom\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Stream\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\SFTP\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\FTP\Configuration())->getConfigTreeBuilder()->getRootNode())
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
