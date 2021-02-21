<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Satellite;

final class SatelliteBuilder implements Satellite\SatelliteBuilderInterface
{
    private string $fromImage;
    private string $workdir;
    /** @var iterable<string> */
    private iterable $composerRequire;
    /** @var iterable<string> */
    private iterable $entrypoint;
    /** @var iterable<string> */
    private iterable $command;
    /** @var iterable<string> */
    private iterable $tags;
    private null|Satellite\FileInterface|Satellite\AssetInterface $composerJsonFile;
    private null|Satellite\FileInterface|Satellite\AssetInterface $composerLockFile;
    /** @var iterable<array<string, string>> */
    private iterable $paths;
    /** @var \AppendIterator<string,Satellite\FileInterface> */
    private iterable $files;

    public function __construct(string $fromImage)
    {
        $this->fromImage = $fromImage;
        $this->workdir = '/var/www/html/';
        $this->composerRequire = [];
        $this->entrypoint = [];
        $this->command = [];
        $this->tags = [];
        $this->composerJsonFile = null;
        $this->composerLockFile = null;
        $this->paths = [];
        $this->files = new \AppendIterator();
    }

    public function withWorkdir(string $path): self
    {
        $this->workdir = $path;

        return $this;
    }

    public function withComposerRequire(string ...$package): self
    {
        array_push($this->composerRequire, ...$package);

        return $this;
    }

    public function withComposerFile(
        Satellite\FileInterface|Satellite\AssetInterface $composerJsonFile,
        null|Satellite\FileInterface|Satellite\AssetInterface $composerLockFile = null
    ): self {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function withFile(
        Satellite\FileInterface|Satellite\AssetInterface $source,
        ?string $destinationPath = null
    ): self {
        if (!$source instanceof Satellite\FileInterface) {
            $source = new Satellite\VirtualFile($source);
        }

        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \ArrayIterator([
            new Satellite\File($destinationPath, $source),
        ]));

        return $this;
    }

    public function withDirectory(Satellite\DirectoryInterface $source, ?string $destinationPath = null): self
    {
        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \RecursiveIteratorIterator($source, \RecursiveIteratorIterator::SELF_FIRST));

        return $this;
    }

    public function withEntrypoint(string ...$entrypoint): self
    {
        $this->entrypoint = $entrypoint;

        return $this;
    }

    public function withCommand(string ...$command): self
    {
        $this->command = $command;

        return $this;
    }

    public function withTags(string ...$tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function build(): Satellite\SatelliteInterface
    {
        $dockerfile = new Dockerfile(
            new Dockerfile\From($this->fromImage),
            new Satellite\Adapter\Docker\Dockerfile\Workdir($this->workdir),
        );

        foreach ($this->paths as [$from, $to]) {
            $dockerfile->push(new Dockerfile\Copy($from, $to));
        }

        if ($this->composerJsonFile !== null) {
            $dockerfile->push(new Satellite\Adapter\Docker\Dockerfile\Copy('composer.json', 'composer.json'));
            $this->files->append(new \ArrayIterator([
                new Satellite\File('composer.json', $this->composerJsonFile),
            ]));

            if ($this->composerLockFile !== null) {
                $dockerfile->push(new Satellite\Adapter\Docker\Dockerfile\Copy('composer.json', 'composer.lock'));
                $this->files->append(new \ArrayIterator([
                    new Satellite\File('composer.lock', $this->composerLockFile),
                ]));
            }

            $dockerfile->push(new Satellite\Adapter\Docker\PHP\Composer());
            $dockerfile->push(new Satellite\Adapter\Docker\PHP\ComposerInstall());
        } elseif (count($this->composerRequire) > 0) {
            $dockerfile->push(new Satellite\Adapter\Docker\PHP\Composer());
            $dockerfile->push(new Satellite\Adapter\Docker\PHP\ComposerInit(sprintf('satellite/%s', substr(hash('sha512', random_bytes(64)), 0, 64))));
            $dockerfile->push(new Satellite\Adapter\Docker\PHP\ComposerMinimumStability('dev'));
        }

        if (count($this->composerRequire) > 0) {
            $dockerfile->push(new Satellite\Adapter\Docker\PHP\ComposerRequire(...$this->composerRequire));
        }

        if (count($this->entrypoint) > 0) {
            $dockerfile->push(new Satellite\Adapter\Docker\Dockerfile\Entrypoint(...$this->entrypoint));
        }

        if (count($this->command) > 0) {
            $dockerfile->push(new Satellite\Adapter\Docker\Dockerfile\Cmd(...$this->command));
        }

        $satellite = new Satellite\Adapter\Docker\Satellite(
            $dockerfile,
            $this->workdir,
            ...$this->files
        );

        $satellite->addTags(...$this->tags);

        return $satellite;
    }
}
