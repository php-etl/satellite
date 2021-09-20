<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Component\Satellite\SatelliteBuilderInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;

interface FactoryInterface
{
    public function configuration(): ConfigurationInterface;

    public function __invoke(array $configuration): SatelliteBuilderInterface;
}
