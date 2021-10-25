<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker\PHP;

use Kiboko\Component\Satellite\Adapter\Docker\Dockerfile;

final class ComposerAutoload implements Dockerfile\LayerInterface
{
    /**
     * @param array<string, array<string, string|list<string>>> $autoloads
     */
    private array $autoloads;

    public function __construct(array $autoloads)
    {
        $this->autoloads = $autoloads;
    }

    public function __toString()
    {
        return (string) new Dockerfile\Run(
            <<<RUN
            set -ex \\
                && {$this->executeAutoload()}
            RUN
        );
    }

    private function executeAutoload(): string
    {
        foreach ($this->autoloads as $type => $autoload) {
            match ($type) {
                'psr4' => $process = "cat composer.json | jq --indent 4 '.autoload.\"psr-4\" |= . + '" . json_encode($autoload) . " | tee composer.json",
            };
        }

        return $process;
    }
}
