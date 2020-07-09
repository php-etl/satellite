<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Docker;

interface AssetInterface
{
    /** @return resource */
    public function asResource();
}