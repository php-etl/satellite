<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Satellite\SatelliteInterface;

interface RuntimeInterface
{
    public function prepare(SatelliteInterface $satellite): void;
    public function build(): array;
}
