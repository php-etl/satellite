<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Tar;

use Kiboko\Component\Packaging\TarArchive;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging;
use Psr\Log\LoggerInterface;

final class Satellite implements Configurator\SatelliteInterface
{
    /** @var string[] */
    private array $imageTags = [];
    /** @var iterable<Packaging\AssetInterface|Packaging\DirectoryInterface> */
    private iterable $files;
    private iterable $dependencies = [];

    public function __construct(
        private readonly string $outputPath,
        Packaging\AssetInterface|Packaging\DirectoryInterface ...$files
    ) {
        $this->files = $files;
    }

    public function addTags(string ...$imageTags): self
    {
        array_push($this->imageTags, ...$imageTags);

        return $this;
    }

    public function withFile(Packaging\AssetInterface|Packaging\DirectoryInterface ...$files): self
    {
        array_push($this->files, ...$files);

        return $this;
    }

    public function dependsOn(string ...$dependencies): self
    {
        array_push($this->dependencies, ...$dependencies);

        return $this;
    }

    public function build(
        LoggerInterface $logger,
    ): void {
        $archive = new TarArchive(...$this->files);

        mkdir(\dirname($this->outputPath), 0o755, true);

        $stream = gzopen($this->outputPath, 'wb');
        \assert(false !== $stream, new \ErrorException(error_get_last()['message'], filename: error_get_last()['file'], line: error_get_last()['line']));
        stream_copy_to_stream($archive->asResource(), $stream);
        gzclose($stream);
    }
}
