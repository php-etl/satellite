<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class Workdir implements LayerInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function __toString()
    {
        return sprintf('WORKDIR %s', $this->path);
    }
}
