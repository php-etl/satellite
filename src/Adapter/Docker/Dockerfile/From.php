<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class From implements LayerInterface
{
    private string $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }

    public function __toString()
    {
        return sprintf('FROM %s', $this->source);
    }
}
