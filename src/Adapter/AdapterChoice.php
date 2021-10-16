<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Component\Satellite;

final class AdapterChoice
{
    public function __invoke(array $configuration): Satellite\SatelliteBuilderInterface
    {
        if (array_key_exists('docker', $configuration)) {
            $factory = new Satellite\Adapter\Docker\Factory();
        } elseif (array_key_exists('filesystem', $configuration)) {
            $factory = new Satellite\Adapter\Filesystem\Factory();
        } elseif (array_key_exists('cloud', $configuration)) {
            $factory = new Satellite\Adapter\Filesystem\Factory();
        } elseif (array_key_exists('amazon_lambda', $configuration)) {
            $factory = new Satellite\Adapter\AmazonLambda\Factory();
        } elseif (array_key_exists('google_cloud_function', $configuration)) {
            $factory = new Satellite\Adapter\GoogleCloudFunction\Factory();
        } else {
            throw new \RuntimeException('No compatible adapter was found for your satellite configuration.');
        }

        return $factory($configuration);
    }
}
