<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;
use Kiboko\Component\Satellite\Runtime\RuntimeInterface;

final class Runtime implements Dockerfile\LayerInterface
{
    private RuntimeInterface $runtime;

    public function __construct(RuntimeInterface $runtime)
    {
        $this->runtime = $runtime;
    }

    public function __toString()
    {
        return (string) new Dockerfile\Copy();
    }
}
