<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class Run implements LayerInterface
{
    private string $command;

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function __toString()
    {
        return sprintf('RUN %s', $this->command);
    }
}
