<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Cloud;

use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class Configuration implements Configurator\AdapterConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $builder = new TreeBuilder('cloud');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->beforeNormalization()
                ->always(function (array $values) {
                    if (array_key_exists('filesystem', $values) && array_key_exists('docker', $values)) {
                        throw new InvalidConfigurationException(sprintf(
                            'Your configuration should either contain the "%s" or the "%s" field, not both.',
                            'filesystem',
                            'docker',
                        ));
                    }

                    if (!array_key_exists('filesystem', $values) && !array_key_exists('docker', $values)) {
                        throw new InvalidConfigurationException(sprintf(
                            'Your configuration must contain at least the "%s" or the "%s" field.',
                            'filesystem',
                            'docker',
                        ));
                    }

                    return $values;
                })
            ->end()
            ->children()
                ->scalarNode('url')->end()
                ->scalarNode('name')->end()
                ->scalarNode('project')->end()
                ->append($this->getFilesystemConfigTreeBuilder()->getRootNode())
                ->append($this->getDockerConfigTreeBuilder()->getRootNode())
            ->end();

        return $builder;
    }

    public function getFilesystemConfigTreeBuilder(): TreeBuilder
    {
        return (new Satellite\Adapter\Filesystem\Configuration())->getConfigTreeBuilder();
    }

    public function getDockerConfigTreeBuilder(): TreeBuilder
    {
        return (new Satellite\Adapter\Docker\Configuration())->getConfigTreeBuilder();
    }
}
