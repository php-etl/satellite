<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Tar;

use Kiboko\Component\Packaging;
use Kiboko\Contract\Configurator;

#[Configurator\Adapter(name: 'tar')] final readonly class Factory implements Configurator\Adapter\FactoryInterface
{
    private Configuration $configuration;

    public function __construct(private string $outputPath)
    {
        $this->configuration = new Configuration();
    }

    public function configuration(): Configurator\AdapterConfigurationInterface
    {
        return $this->configuration;
    }

    public function __invoke(array $configuration): Configurator\SatelliteBuilderInterface
    {
        $builder = new SatelliteBuilder($this->outputPath);

        if (\array_key_exists('composer', $configuration)) {
            if (\array_key_exists('from_local', $configuration['composer']) && true === $configuration['composer']['from_local']) {
                if (file_exists('composer.lock')) {
                    $builder->withComposerFile(new Packaging\Asset\LocalFile('composer.json'), new Packaging\Asset\LocalFile('composer.lock'));
                } else {
                    $builder->withComposerFile(new Packaging\Asset\LocalFile('composer.json'));
                }
                if (file_exists('vendor')) {
                    $builder->withDirectory(new Packaging\Directory('vendor/'));
                }
            }

            if (\array_key_exists('autoload', $configuration['composer']) && \array_key_exists('psr4', $configuration['composer']['autoload'])) {
                foreach ($configuration['composer']['autoload']['psr4'] as $namespace => $autoload) {
                    $builder->withComposerPSR4Autoload($namespace, ...$autoload['paths']);
                }
            }

            if (\array_key_exists('require', $configuration['composer'])) {
                $builder->withComposerRequire(...$configuration['composer']['require']);
            }
        }

        return $builder;
    }
}
