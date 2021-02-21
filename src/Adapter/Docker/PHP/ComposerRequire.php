<?php

declare(strict_types=1);

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
                && composer require --prefer-dist --no-suggest --no-progress --prefer-stable --sort-packages --optimize-autoloader %s
            RUN, implode(' ', $this->packages)));
    }
}
