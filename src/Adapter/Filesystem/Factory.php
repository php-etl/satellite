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
            if (array_key_exists('from_local', $configuration['composer']) && $configuration['composer']['from_local'] === true) {
                if (file_exists('composer.json') && file_exists('composer.lock')) {
                    $builder->withComposerFile(
                        new Packaging\File('composer.json', new Packaging\Asset\LocalFile('composer.json')),
                        new Packaging\File('composer.lock', new Packaging\Asset\LocalFile('composer.lock')),
                    );
                } else {
                    throw new Satellite\Exception\LocalFileNotFoundException('composer.json, composer.lock');
                }
                if (file_exists('vendor')) {
                    $builder->withDirectory(new Packaging\Directory('vendor/'));
                } else {
                    throw new Satellite\Exception\LocalFileNotFoundException('vendor/. Please run `composer install`.');
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
                    throw new Satellite\Exception\LocalFileNotFoundException($item['from']);
                }
            }
        }

        return $builder;
    }
}
