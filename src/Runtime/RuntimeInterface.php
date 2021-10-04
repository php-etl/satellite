<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Satellite\SatelliteInterface;
use Psr\Log\LoggerInterface;
use Kiboko\Contract\Configurator\FactoryInterface;

interface RuntimeInterface
{
    public function prepare(FactoryInterface $service, SatelliteInterface $satellite, LoggerInterface $logger): void;
    public function getFilename(): string;
}
