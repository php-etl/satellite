<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite\SatelliteBuilderInterface;
use Kiboko\Contract\Packaging as PackagingContract;

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
    private null|PackagingContract\FileInterface|PackagingContract\AssetInterface $composerJsonFile;
    private null|PackagingContract\FileInterface|PackagingContract\AssetInterface $composerLockFile;
    /** @var iterable<array<string, string>> */
    private iterable $paths;
    /** @var \AppendIterator<string,PackagingContract\FileInterface> */
    private iterable $files;
    private array $composerAutoload;

    public function __construct(string $fromImage)
    {
        $this->composerAutoload = [];
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

    public function withComposerPSR4Autoload(array $autoloads): SatelliteBuilderInterface
    {
        if (!array_key_exists('psr4', $this->composerAutoload)) {
            $this->composerAutoload['psr4'] = [];
        }

        array_push($this->composerAutoload['psr4'], $autoloads);

        return $this;
    }

    public function withComposerRequire(string ...$package): self
    {
        array_push($this->composerRequire, ...$package);

        return $this;
    }

    public function withComposerFile(
        PackagingContract\FileInterface|PackagingContract\AssetInterface $composerJsonFile,
        null|PackagingContract\FileInterface|PackagingContract\AssetInterface $composerLockFile = null
    ): self {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function withFile(
        PackagingContract\FileInterface|PackagingContract\AssetInterface $source,
        ?string $destinationPath = null
    ): self {
        if (!$source instanceof PackagingContract\FileInterface) {
            $source = new Packaging\VirtualFile($source);
        }

        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \ArrayIterator([
            new Packaging\File($destinationPath, $source),
        ]));

        return $this;
    }

    public function withDirectory(PackagingContract\DirectoryInterface $source, ?string $destinationPath = null): self
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
                new Packaging\File('composer.json', $this->composerJsonFile),
            ]));

            if ($this->composerLockFile !== null) {
                $dockerfile->push(new Satellite\Adapter\Docker\Dockerfile\Copy('composer.json', 'composer.lock'));
                $this->files->append(new \ArrayIterator([
                    new Packaging\File('composer.lock', $this->composerLockFile),
                ]));
            }

            $dockerfile->push(new Satellite\Adapter\Docker\PHP\Composer());
            $dockerfile->push(new Satellite\Adapter\Docker\PHP\ComposerInstall());
        } else {
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
