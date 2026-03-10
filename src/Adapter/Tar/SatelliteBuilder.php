<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Tar;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging as PackagingContract;

final class SatelliteBuilder implements Configurator\SatelliteBuilderInterface
{
    /** @var string[] */
    private array $composerRequire = [];
    private null|PackagingContract\AssetInterface|PackagingContract\FileInterface $composerJsonFile = null;
    private null|PackagingContract\AssetInterface|PackagingContract\FileInterface $composerLockFile = null;
    /** @var \AppendIterator<string,PackagingContract\FileInterface, \Iterator<string,PackagingContract\FileInterface>> */
    private readonly iterable $files;
    /** @var array<string, array<string, string|array<int|string, string>>> */
    private array $composerAutoload = [
        'psr4' => [
            'GyroscopsGenerated\\' => './',
        ],
    ];

    public function __construct(private readonly string $outputPath)
    {
        $this->files = new \AppendIterator();
    }

    public function withComposerPSR4Autoload(string $namespace, string ...$paths): self
    {
        if (!\array_key_exists('psr4', $this->composerAutoload)) {
            $this->composerAutoload['psr4'] = [];
        }
        $this->composerAutoload['psr4'][$namespace] = $paths;

        return $this;
    }

    public function withComposerRequire(string ...$package): self
    {
        array_push($this->composerRequire, ...$package);

        return $this;
    }

    public function withComposerFile(
        PackagingContract\AssetInterface|PackagingContract\FileInterface $composerJsonFile,
        PackagingContract\AssetInterface|PackagingContract\FileInterface|null $composerLockFile = null
    ): self {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function withFile(
        PackagingContract\AssetInterface|PackagingContract\FileInterface $source,
        ?string $destinationPath = null
    ): self {
        if (!$source instanceof PackagingContract\FileInterface) {
            $source = new Packaging\VirtualFile($source);
        }

        $destPath = $destinationPath ?? $source->getPath();
        $this->files->append(new \ArrayIterator([
            $destPath => new Packaging\File($destPath, $source),
        ]));

        return $this;
    }

    public function withDirectory(PackagingContract\DirectoryInterface $source, ?string $destinationPath = null): self
    {
        $this->files->append(new \RecursiveIteratorIterator($source, \RecursiveIteratorIterator::SELF_FIRST));

        return $this;
    }

    public function build(): Configurator\SatelliteInterface
    {
        $satellite = new Satellite\Adapter\Tar\Satellite(
            $this->outputPath,
            ...$this->files
        );

        if (null !== $this->composerJsonFile) {
            $composerJson = $this->composerJsonFile instanceof PackagingContract\FileInterface
                ? $this->composerJsonFile
                : new Packaging\VirtualFile($this->composerJsonFile);
            $satellite->withFile($composerJson);

            if (null !== $this->composerLockFile) {
                $composerLock = $this->composerLockFile instanceof PackagingContract\FileInterface
                    ? $this->composerLockFile
                    : new Packaging\VirtualFile($this->composerLockFile);
                $satellite->withFile($composerLock);
            }
        }

        return $satellite;
    }
}
