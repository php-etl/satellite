<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Satellite\SatelliteInterface;
use PhpParser\Node;
use Psr\Log\LoggerInterface;

interface RuntimeInterface
{
    public function prepare(SatelliteInterface $satellite, LoggerInterface $logger): void;
    public function getFilename(): string;
}
