<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Dockerfile;
use Kiboko\Component\Packaging\TarArchive;
use Kiboko\Component\Satellite\Adapter\ComposerFailureException;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\Promise\Deferred;
use React\Stream\ReadableResourceStream;

use function React\Async\await;
use function React\Promise\Timer\timeout;

final class Satellite implements Configurator\SatelliteInterface
{
    /** @var string[] */
    private array $imageTags = [];
    /** @var array<int|string, Packaging\DirectoryInterface|Packaging\FileInterface> */
    private array $files;
    /** @var string[] */
    private array $dependencies = [];

    public function __construct(
        private readonly Dockerfile\Dockerfile $dockerfile,
        private readonly string $workdir,
        Packaging\DirectoryInterface|Packaging\FileInterface ...$files
    ) {
        $this->files = $files;
    }

    public function addTags(string ...$imageTags): self
    {
        array_push($this->imageTags, ...$imageTags);

        return $this;
    }

    public function withFile(Packaging\DirectoryInterface|Packaging\FileInterface ...$files): self
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
        $archive = new TarArchive($this->dockerfile, ...array_values($this->files));

        $iterator = function (iterable $tags) {
            foreach ($tags as $tag) {
                yield '-t';
                yield $tag;
            }
        };

        $command = ['docker', 'build', '--rm', '-', ...iterator_to_array($iterator($this->imageTags))];

        $process = new Process(
            implode(' ', array_map(fn ($part) => escapeshellarg((string) $part), $command)),
            $this->workdir,
        );

        $process->start();

        $input = new ReadableResourceStream($archive->asResource());

        if ($process->stdin !== null) {
            $input->pipe($process->stdin);
        }

        $this->execute($logger, $process);

        $exitCode = $process->getExitCode();
        if ($exitCode !== null && 0 !== $exitCode) {
            throw new \RuntimeException('Process exited unexpectedly.');
        }
    }

    private function execute(
        LoggerInterface $logger,
        Process $process,
        float $timeout = 300
    ): void {
        if ($process->stdout !== null) {
            $process->stdout->on('data', function ($chunk) use ($logger): void {
                $logger->debug($chunk);
            });
        }
        if ($process->stderr !== null) {
            $process->stderr->on('data', function ($chunk) use ($logger): void {
                $logger->info($chunk);
            });
        }

        $deferred = new Deferred();

        $process->on('exit', function () use ($deferred): void {
            $deferred->resolve(null);
        });

        $logger->debug(sprintf('Starting process "%s".', $process->getCommand()));

        await(timeout($deferred->promise(), $timeout));

        $exitCode = $process->getExitCode();
        if ($exitCode !== null && 0 !== $exitCode) {
            throw new ComposerFailureException($process->getCommand(), sprintf('Process exited unexpectedly with output: %s', $exitCode), $exitCode);
        }
    }
}
