<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Contract\Configurator\FactoryInterface;
use Kiboko\Contract\Configurator\SatelliteInterface;
use Psr\Log\LoggerInterface;

interface RuntimeInterface
{
    public function prepare(FactoryInterface $service, SatelliteInterface $satellite, LoggerInterface $logger): void;

    public function getFilename(): string;
}
