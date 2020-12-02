<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\ETL\Satellite\Adapter\Docker\Dockerfile;

final class ComposerMinimumStability implements Dockerfile\LayerInterface
{
    private string $minimumStability;

    public function __construct(string $minimumStability)
    {
        $this->minimumStability = $minimumStability;
    }

    public function __toString()
    {
        return (string) new Dockerfile\Run(sprintf(<<<RUN
            set -ex \\
                && mkdir -p /var/www/html \\
                && cd /var/www/html \\
                && composer config minimum-stability %s
            RUN, $this->minimumStability));
    }
}
