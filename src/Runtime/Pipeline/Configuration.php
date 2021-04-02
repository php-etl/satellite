<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\Spreadsheet;
use Kiboko\Plugin\JSON;
use Kiboko\Plugin\API;
use Kiboko\Plugin\Log;
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
                ->arrayNode('steps')
                    ->isRequired()
                    ->arrayPrototype()
                        ->append((new Akeneo\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Sylius\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new CSV\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Spreadsheet\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new JSON\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new FastMap\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new API\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Custom\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Log\Configuration())->getConfigTreeBuilder()->getRootNode())
                        ->append((new Satellite\Plugin\Stream\Configuration())->getConfigTreeBuilder()->getRootNode())
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
