<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class ComposerInit implements Dockerfile\LayerInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __toString()
    {
        return (string) new Dockerfile\Run(sprintf(
            <<<RUN
            set -ex \\
                && composer init --no-interaction --name=%s && pwd
            RUN,
            $this->name
        ));
    }
}
