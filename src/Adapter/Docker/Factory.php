<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use Kiboko\Contract\Configurator\Adapter;
use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

#[Adapter(name: "docker")]
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
        $builder = new SatelliteBuilder($configuration['docker']['from']);

        if (isset($configuration['docker']['workdir'])) {
            $builder->withWorkdir($configuration['docker']['workdir']);
        }
        if (isset($configuration['docker']['tags'])) {
            $builder->withTags(...$configuration['docker']['tags']);
        }

        if (isset($configuration['docker']['include']) && is_iterable($configuration['docker']['include'])) {
            foreach ($configuration['docker']['include'] as $path) {
                if (is_dir($path)) {
                    $builder->withDirectory(new Packaging\Directory($path));
                } else {
                    $builder->withFile(new Packaging\Asset\LocalFile($path));
                }
            }
        }

        if (isset($configuration['include'])) {
            foreach ($configuration['include'] as $path) {
                if (is_dir($path)) {
                    $builder->withDirectory(new Packaging\Directory($path));
                } else {
                    $builder->withFile(new Packaging\Asset\LocalFile($path));
                }
            }
        }

        if (array_key_exists('composer', $configuration)) {
            if (array_key_exists('from-local', $configuration['composer']) && $configuration['composer']['from-local'] === true) {
                if (file_exists('composer.lock')) {
                    $builder->withComposerFile(new Packaging\Asset\LocalFile('composer.json'), new Packaging\Asset\LocalFile('composer.lock'));
                } else {
                    $builder->withComposerFile(new Packaging\Asset\LocalFile('composer.json'));
                }
                if (file_exists('vendor')) {
                    $builder->withDirectory(new Packaging\Directory('vendor/'));
                }
            }

            if (array_key_exists('autoload', $configuration['composer']) && array_key_exists('psr4', $configuration['composer']['autoload'])) {
                $builder->withComposerPSR4Autoload($configuration['composer']['autoload']['psr4']);
            }

            if (array_key_exists('require', $configuration['composer'])) {
                $builder->withComposerRequire(...$configuration['composer']['require']);
            }
        }

        return $builder;
    }
}
