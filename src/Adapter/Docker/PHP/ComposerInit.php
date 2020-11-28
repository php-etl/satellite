<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\ETL\Satellite\Adapter\Docker\Dockerfile;

final class ComposerInit implements Dockerfile\LayerInterface
{
    public function __toString()
    {
        return (string) new Dockerfile\Run(<<<RUN
            composer init --no-interaction
            RUN
        );
    }
}
