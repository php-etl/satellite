<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Docker\Asset;

use Kiboko\Component\ETL\Satellite\Docker\AssetInterface;

final class File implements AssetInterface
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