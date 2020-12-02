<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Runtime;

use Kiboko\Component\ETL\Satellite\SatelliteInterface;

interface RuntimeInterface
{
    public function prepare(SatelliteInterface $satellite): void;
    public function build(): array;
}
