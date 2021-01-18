<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class ComposerRequire implements Dockerfile\LayerInterface
{
    private iterable $packages;

    public function __construct(string ...$packages)
    {
        $this->packages = $packages;
    }

    public function __toString()
    {
        return (string) new Dockerfile\Run(sprintf(<<<RUN
            set -ex \\
                && mkdir -p /var/www/html \\
                && cd /var/www/html \\
                && composer require %s
            RUN, implode(' ', $this->packages)));
    }
}
