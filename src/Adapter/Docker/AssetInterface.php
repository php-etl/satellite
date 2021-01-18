<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

interface AssetInterface
{
    /** @return resource */
    public function asResource();
}
