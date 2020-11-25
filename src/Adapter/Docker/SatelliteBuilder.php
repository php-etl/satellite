<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker;

use Kiboko\Component\ETL\Satellite\SatelliteBuilderInterface;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;

final class SatelliteBuilder implements SatelliteBuilderInterface
{
    private string $phpVersion;
    /** @var iterable<string> */
    private iterable $phpExtensions;
    private ?string $composerJsonFile;
    private ?string $composerLockFile;
    /** @var iterable<array<string, string>> */
    private \Iterator $paths;
    /** @var iterable<string,\SplFileInfo> */
    private \Iterator $files;

    public function __construct(string $phpVersion)
    {
        $this->phpVersion = $phpVersion;
        $this->phpExtensions = [];
        $this->composerJsonFile = null;
        $this->composerLockFile = null;
        $this->paths = new \ArrayIterator();
        $this->files = new \AppendIterator();
    }

    public function setPHPVersion(string $versionConstraint): SatelliteBuilderInterface
    {
        $this->phpVersion = $versionConstraint;

        return $this;
    }

    public function addPHPExtension(string $extension): SatelliteBuilderInterface
    {
        $this->phpExtensions[] = $extension;

        return $this;
    }

    public function addComposerInstall(string $composerJsonFile, ?string $composerLockFile = null): SatelliteBuilderInterface
    {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function addFile(string $sourcePath, ?string $destinationPath = null): SatelliteBuilderInterface
    {
        $this->paths->append([$sourcePath, $destinationPath]);

        $this->files->append(new \ArrayIterator([
            new File($destinationPath, new Asset\File($sourcePath)),
        ]));

        return $this;
    }

    public function addDirectory(string $sourcePath, ?string $destinationPath = null): SatelliteBuilderInterface
    {
        $this->paths->append([$sourcePath, $destinationPath]);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath), \RecursiveIteratorIterator::SELF_FIRST
        );

        $this->files->append((function(\Iterator $iterator, string $sourcePath, string $destinationPath) {
            /** @var \SplFileInfo $file */
            foreach ($iterator as $fileInfo) {
                yield new File(
                    preg_replace('/^'.preg_quote($sourcePath, '/').'/', $destinationPath, $fileInfo->getPathname()),
                    new Asset\File($fileInfo->getPathname())
                );
            }
        })($iterator, $sourcePath, $sourcePath ?? $destinationPath));

        return $this;
    }

    public function build(): SatelliteInterface
    {
        return new Satellite(
            uniqid(),
            new Dockerfile(
                new Dockerfile\From(sprintf('kiboko/php:%s-cli', $this->phpVersion)),
                new PHP\Extension\ZMQ(),
                new PHP\ComposerRequire('ramsey/uuid'),
                new Dockerfile\Workdir('/app/'),
                ...(function(array $paths){
                    yield new Dockerfile\Copy(...$paths);
                })($this->paths)
            ),
            ...$this->files
        );
    }
}