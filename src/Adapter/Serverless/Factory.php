<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Serverless;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;

final class Factory implements Satellite\Adapter\FactoryInterface
{
    public function __invoke(array $configuration): Satellite\SatelliteBuilderInterface
    {
        $builder = new SatelliteBuilder();

        if (array_key_exists('composer', $configuration)) {
            if (array_key_exists('from_local', $configuration['composer']) && $configuration['composer']['from_local'] === true) {
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

            if (array_key_exists('composer', $configuration) && array_key_exists('require', $configuration['composer'])) {
                $builder->withComposerRequire(...$configuration['composer']['require']);
            }
        }

        return $builder;
    }
}
