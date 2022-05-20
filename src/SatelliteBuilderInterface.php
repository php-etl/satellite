<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Contract\Packaging;

interface SatelliteBuilderInterface
{
    public function withComposerPSR4Autoload(string $namespace, string ...$paths): self;

    public function withComposerRequire(string ...$package): self;

    public function withComposerFile(
        Packaging\FileInterface|Packaging\AssetInterface $composerJsonFile,
        null|Packaging\FileInterface|Packaging\AssetInterface $composerLockFile = null
    ): self;

    public function withFile(
        Packaging\FileInterface|Packaging\AssetInterface $source,
        ?string $destinationPath = null
    ): self;

    public function withDirectory(
        Packaging\DirectoryInterface $source,
        ?string $destinationPath = null
    ): self;

    public function build(): SatelliteInterface;
}
