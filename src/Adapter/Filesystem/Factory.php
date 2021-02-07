<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\SatelliteBuilderInterface;

final class Factory implements Satellite\Adapter\FactoryInterface
{
    public function __invoke(array $configuration): SatelliteBuilderInterface
    {
        $builder = new SatelliteBuilder($configuration['filesystem']['path']);

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
