<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Filesystem;

interface AssetInterface
{
    /** @return resource */
    public function asResource();
}
