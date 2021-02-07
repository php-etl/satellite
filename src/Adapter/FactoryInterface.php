<?php

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Component\Satellite\SatelliteBuilderInterface;

interface FactoryInterface
{
    public function __invoke(array $configuration): SatelliteBuilderInterface;
}
