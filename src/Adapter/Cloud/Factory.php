<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Cloud;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator\Adapter;
use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

#[Adapter(name: "cloud")]
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
        return new SatelliteBuilder();
    }
}
