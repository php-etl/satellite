<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class ComposerInstall implements Dockerfile\LayerInterface
{
    public function __toString()
    {
        return (string) new Dockerfile\Run(
            <<<RUN
            composer install --no-dev --optimize-autoloader
            RUN
        );
    }
}
