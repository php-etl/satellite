<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

interface SatelliteBuilderInterface
{
    public function withComposerFile(FileInterface|AssetInterface $composerJsonFile, null|FileInterface|AssetInterface $composerLockFile = null): SatelliteBuilderInterface;

    public function withFile(FileInterface|AssetInterface $source, ?string $destinationPath = null): SatelliteBuilderInterface;
    public function withDirectory(DirectoryInterface $source, ?string $destinationPath = null): SatelliteBuilderInterface;

    public function build(): SatelliteInterface;
}
