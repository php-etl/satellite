<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class ComposerAddGithubRepository implements Dockerfile\LayerInterface
{
    public function __construct(
        private string $name,
        private string $url
    ) {}

    public function __toString()
    {
        return (string) new Dockerfile\Run(sprintf(<<<RUN
            set -ex \\
                && composer config repositories.%s github %s
            RUN, $this->name, $this->url));
    }
}
