<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\ETL\Satellite\Adapter\Docker\Dockerfile;

final class ComposerRequire implements Dockerfile\LayerInterface
{
    private iterable $packges;

    public function __construct(string ...$packges)
    {
        $this->packges = $packges;
    }

    public function __toString()
    {
        return (string) new Dockerfile\Run(sprintf(<<<RUN
            set -ex \\
                && mkdir -p /var/www/html \\
                && cd /var/www/html \\
                && composer require %s
            RUN, implode(' ', $this->packges)));
    }
}
