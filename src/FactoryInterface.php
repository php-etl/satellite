<?php

namespace Kiboko\Component\Satellite;

interface FactoryInterface
{
    public function __invoke(array $configuration): SatelliteBuilderInterface;
}
