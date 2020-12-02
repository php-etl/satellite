<?php

declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker;

use Kiboko\Component\ETL\Satellite\Adapter\Docker\PHP\ComposerRequire;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class Satellite implements SatelliteInterface
{
    private string $imageTag;
    private Dockerfile $dockerfile;
    private iterable $files;
    private iterable $dependencies;

    public function __construct(
        string $imageTag,
        Dockerfile $dockerfile,
        FileInterface ...$files
    ) {
        $this->imageTag = $imageTag;
        $this->dockerfile = $dockerfile;
        $this->files = $files;
        $this->dependencies = [];
    }

    public function dependsOn(string ...$dependencies): void
    {
        array_push($this->dependencies, ...$dependencies);
    }

    public function push(FileInterface ...$files): void
    {
        array_push($this->files, ...$files);
    }

    public function build(LoggerInterface $logger): void
    {
        $this->dockerfile->push(
            new ComposerRequire(...$this->dependencies),
        );

        $archive = new TarArchive($this->dockerfile, ...$this->files);

        $process = new Process([
            'docker', 'build', '-t', $this->imageTag, '--rm', '-'
        ]);

        $process->setInput($archive->asResource());

        $process->setTimeout(300);

        $process->run(function ($type, $buffer) use ($logger) {
            if (Process::ERR === $type) {
                $logger->error($buffer);
            } else {
                $logger->debug($buffer);
            }
        });

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException('Process exited unexpectedly.');
        }
    }
}
