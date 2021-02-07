<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Satellite\SatelliteInterface;
use Psr\Log\LoggerInterface;

interface RuntimeInterface
{
    public function prepare(SatelliteInterface $satellite, LoggerInterface $logger): void;
    public function build(): array;
}
