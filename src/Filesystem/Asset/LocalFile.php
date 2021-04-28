<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Filesystem\Asset;

use Kiboko\Component\Satellite\Filesystem\AssetInterface;

final class LocalFile implements AssetInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /** @return resource */
    public function asResource()
    {
        return fopen($this->path, 'rb');
    }
}