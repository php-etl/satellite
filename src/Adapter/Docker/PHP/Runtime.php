<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\ETL\Satellite\Adapter\Docker\Dockerfile;
use Kiboko\Component\ETL\Satellite\Runtime\RuntimeInterface;

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
