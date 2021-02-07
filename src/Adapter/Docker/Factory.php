<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Satellite;

final class Factory implements Satellite\Adapter\FactoryInterface
{
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
                    $builder->withDirectory(new Satellite\Directory($path));
                } else {
                    $builder->withFile(new Satellite\Asset\File($path));
                }
            }
        }

        if (isset($configuration['include'])) {
            foreach ($configuration['include'] as $path) {
                if (is_dir($path)) {
                    $builder->withDirectory(new Satellite\Directory($path));
                } else {
                    $builder->withFile(new Satellite\Asset\File($path));
                }
            }
        }

        if (array_key_exists('composer', $configuration)) {
            if (array_key_exists('from-local', $configuration['composer']) && $configuration['composer']['from-local'] === true) {
                if (file_exists('composer.lock')) {
                    $builder->withComposerFile(new Satellite\Asset\File('composer.json'), new Satellite\Asset\File('composer.lock'));
                } else {
                    $builder->withComposerFile(new Satellite\Asset\File('composer.json'));
                }
                if (file_exists('vendor')) {
                    $builder->withDirectory(new Satellite\Directory('vendor/'));
                }
            }

            if (array_key_exists('composer', $configuration) && array_key_exists('require', $configuration['composer'])) {
                $builder->withComposerRequire(...$configuration['composer']['require']);
            }
        }

        return $builder;
    }
}
