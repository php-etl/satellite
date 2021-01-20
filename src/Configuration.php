<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite\Configuration\PipelineConfiguration;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\CSV;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('satellite');

        $builder->getRootNode()
            ->beforeNormalization()
                ->always(function ($data) {
                    if (array_key_exists('docker', $data) && array_key_exists('filesystem', $data)) {
                        throw new InvalidConfigurationException('You should either specify the "docker" or the "filesystem" options.');
                    }

                    return $data;
                })
            ->end()
            ->children()
                ->append((new Configuration\ComposerConfiguration())->getConfigTreeBuilder()->getRootNode())
                ->append((new Adapter\Docker\Configuration())->getConfigTreeBuilder()->getRootNode())
                ->append((new Adapter\Filesystem\Configuration())->getConfigTreeBuilder()->getRootNode())
//                ->arrayNode('include')
//                    ->scalarPrototype()->end()
//                ->end()
                ->append((new PipelineConfiguration())->getConfigTreeBuilder()->getRootNode())
            ->end();

        return $builder;
    }
}
