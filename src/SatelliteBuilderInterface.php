<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

interface SatelliteBuilderInterface
{
    public function withComposerFile(
        Filesystem\FileInterface|Filesystem\AssetInterface $composerJsonFile,
        null|Filesystem\FileInterface|Filesystem\AssetInterface $composerLockFile = null
    ): SatelliteBuilderInterface;

    public function withFile(
        Filesystem\FileInterface|Filesystem\AssetInterface $source,
        ?string $destinationPath = null
    ): SatelliteBuilderInterface;

    public function withDirectory(
        Filesystem\DirectoryInterface $source,
        ?string $destinationPath = null
    ): SatelliteBuilderInterface;

    public function build(): SatelliteInterface;
}
