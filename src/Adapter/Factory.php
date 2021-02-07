<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Component\Satellite;

final class Factory implements FactoryInterface
{
    public function __invoke(array $configuration): Satellite\SatelliteBuilderInterface
    {
        if (array_key_exists('docker', $configuration)) {
            $factory = new Satellite\Adapter\Docker\Factory();
        } else if (array_key_exists('filesystem', $configuration)) {
            $factory = new Satellite\Adapter\Filesystem\Factory();
        } else {
            throw new \RuntimeException('No compatible adapter was found for your satellite configuration.');
        }

        return $factory($configuration);
    }
}
