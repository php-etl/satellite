<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Dockerfile;
use Kiboko\Component\Packaging\TarArchive;
use Kiboko\Component\Satellite\SatelliteInterface;
use Kiboko\Contract\Packaging;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class Satellite implements SatelliteInterface
{
    /** @var string[] */
    private array $imageTags;
    /** @var iterable<Packaging\DirectoryInterface|Packaging\FileInterface> */
    private iterable $files;
    private iterable $dependencies;

    public function __construct(
        private Dockerfile\Dockerfile $dockerfile,
        private string $workdir,
        Packaging\FileInterface|Packaging\DirectoryInterface ...$files
    ) {
        $this->imageTags = [];
        $this->files = $files;
        $this->dependencies = [];
    }

    public function addTags(string ...$imageTags): self
    {
        array_push($this->imageTags, ...$imageTags);

        return $this;
    }

    public function withFile(Packaging\FileInterface|Packaging\DirectoryInterface ...$files): self
    {
        array_push($this->files, ...$files);

        foreach ($files as $file) {
            $this->dockerfile->push(new Dockerfile\Dockerfile\Copy($file->getPath(), $this->workdir));
        }

        return $this;
    }

    public function dependsOn(string ...$dependencies): self
    {
        array_push($this->dependencies, ...$dependencies);
        $this->dockerfile->push(new Dockerfile\PHP\ComposerRequire(...$dependencies));

        return $this;
    }

    public function build(
        LoggerInterface $logger,
    ): void {
        $archive = new TarArchive($this->dockerfile, ...$this->files);

        $iterator = function (iterable $tags) {
            foreach ($tags as $tag) {
                yield '-t';
                yield $tag;
            }
        };

        $process = new Process([
            'docker', 'build', '--rm', '-', ...iterator_to_array($iterator($this->imageTags))
        ]);

        $process->setInput($archive->asResource());

        $process->setTimeout(300);

        $process->run(function ($type, $buffer) use ($logger) {
            if (Process::ERR === $type) {
                $logger->info($buffer);
            } else {
                $logger->debug($buffer);
            }
        });

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException('Process exited unexpectedly.');
        }
    }
}
