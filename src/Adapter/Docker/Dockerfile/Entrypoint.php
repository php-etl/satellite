<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class Entrypoint implements LayerInterface
{
    private iterable $entrypoint;

    public function __construct(string ...$entrypoint)
    {
        $this->entrypoint = $entrypoint;
    }

    public function __toString()
    {
        return sprintf('ENTRYPOINT [%s]', implode(', ', $this->entrypoint));
    }
}
