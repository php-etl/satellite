<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Component\Satellite\SatelliteBuilderInterface;
use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

interface FactoryInterface
{
    public function configuration(): AdapterConfigurationInterface;

    public function __invoke(array $configuration): SatelliteBuilderInterface;
}
