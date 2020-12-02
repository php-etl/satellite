<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite;

interface SatelliteBuilderInterface
{
    public function fromImage(string $fromImage): SatelliteBuilderInterface;
    public function addPHPExtension(string $extension): SatelliteBuilderInterface;

    public function addComposerInstall(string $composerJsonFile, ?string $composerLockFile = null): SatelliteBuilderInterface;

    public function addFile(string $sourcePath, ?string $destinationPath = null): SatelliteBuilderInterface;
    public function addDirectory(string $sourcePath, ?string $destinationPath = null): SatelliteBuilderInterface;

    public function build(): SatelliteInterface;
}
