<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use Kiboko\Contract\Configurator\Adapter;
use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

#[Adapter(name: "filesystem")]
final class Factory implements Satellite\Adapter\FactoryInterface
{
    private Configuration $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function configuration(): AdapterConfigurationInterface
    {
        return $this->configuration;
    }

    public function __invoke(array $configuration): Satellite\SatelliteBuilderInterface
    {
        $builder = new SatelliteBuilder($configuration['filesystem']['path']);

        if (array_key_exists('composer', $configuration)) {
            if (array_key_exists('from-local', $configuration['composer']) && $configuration['composer']['from-local'] === true) {
                if (file_exists('composer.lock')) {
                    $builder->withComposerFile(
                        new Packaging\Asset\LocalFile('composer.json'),
                        new Packaging\Asset\LocalFile('composer.lock'),
                    );
                } else {
                    $builder->withComposerFile(
                        new Packaging\Asset\LocalFile('composer.json'),
                    );
                }
                if (file_exists('vendor')) {
                    $builder->withDirectory(new Packaging\Directory('vendor/'));
                }
            }

            if (array_key_exists('autoload', $configuration['composer']) && array_key_exists('psr4', $configuration['composer']['autoload'])) {
                foreach ($configuration['composer']['autoload']['psr4'] as $namespace => $autoload) {
                    $builder->withComposerPSR4Autoload($namespace, ...$autoload['paths']);
                }
            }

            if (array_key_exists('require', $configuration['composer'])) {
                $builder->withComposerRequire(...$configuration['composer']['require']);
            }
        }

        if (array_key_exists('copy', $configuration['filesystem'])) {
            foreach ($configuration['filesystem']['copy'] as $item) {
                if (is_dir($item['from'])) {
                    $builder->withDirectory(
                        source: new Packaging\Directory($item['from']),
                        destinationPath: $item['to']
                    );
                } elseif (is_file($item['from'])) {
                    $builder->withFile(
                        source: new Packaging\File(
                            path: $item['from'],
                            content: new Packaging\Asset\LocalFile($item['from'])
                        ),
                        destinationPath: $item['to']
                    );
                } else {
                    throw new \Exception(sprintf('Cannot copy, no directory or file was found with path: %s', $item['from']));
                }
            }
        }

        return $builder;
    }
}
