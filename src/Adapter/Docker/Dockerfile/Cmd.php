<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class Cmd implements LayerInterface
{
    private iterable $command;

    public function __construct(string ...$command)
    {
        $this->command = $command;
    }

    public function __toString()
    {
        return sprintf('CMD [%s]', implode(', ', $this->command));
    }
}
