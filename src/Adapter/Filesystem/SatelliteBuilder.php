<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Satellite;

final class SatelliteBuilder implements Satellite\SatelliteBuilderInterface
{
    private string $workdir;
    /** @var iterable<string> */
    private iterable $composerRequire;
    private null|Satellite\Filesystem\FileInterface|Satellite\Filesystem\AssetInterface $composerJsonFile;
    private null|Satellite\Filesystem\FileInterface|Satellite\Filesystem\AssetInterface $composerLockFile;
    /** @var iterable<array<string, string>> */
    private iterable $paths;
    /** @var \AppendIterator<string,Satellite\Filesystem\FileInterface> */
    private iterable $files;

    public function __construct(string $workdir)
    {
        $this->workdir = $workdir;
        $this->composerRequire = [];
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
        Satellite\Filesystem\FileInterface|Satellite\Filesystem\AssetInterface $composerJsonFile,
        null|Satellite\Filesystem\FileInterface|Satellite\Filesystem\AssetInterface $composerLockFile = null
    ): self {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function withFile(
        Satellite\Filesystem\FileInterface|Satellite\Filesystem\AssetInterface $source,
        ?string $destinationPath = null
    ): self {
        if (!$source instanceof Satellite\Filesystem\FileInterface) {
            $source = new Satellite\Filesystem\VirtualFile($source);
        }

        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \ArrayIterator([
            new Satellite\Filesystem\File($destinationPath, $source),
        ]));

        return $this;
    }

    public function withDirectory(Satellite\Filesystem\DirectoryInterface $source, ?string $destinationPath = null): self
    {
        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \RecursiveIteratorIterator($source, \RecursiveIteratorIterator::SELF_FIRST));

        return $this;
    }

    public function build(): Satellite\SatelliteInterface
    {
        if (!file_exists($this->workdir)) {
            mkdir($this->workdir, 0775, true);
        }

        $satellite = new Satellite\Adapter\Filesystem\Satellite(
            $this->workdir,
        );

        $composer = new Composer($this->workdir);
        if ($this->composerJsonFile !== null) {
            $satellite->withFile($this->composerJsonFile);
            if ($this->composerLockFile !== null) {
                $satellite->withFile($this->composerLockFile);
            }
            $composer->install();
        } elseif (count($this->composerRequire) > 0) {
            $composer->init(sprintf('satellite/%s', substr(hash('sha512', random_bytes(64)), 0, 64)));
            $composer->minimumStability('dev');
        }

        $composer->require(...$this->composerRequire);

        return $satellite;
    }
}
