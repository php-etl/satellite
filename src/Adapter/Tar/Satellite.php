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
    /** @var array<int|string, Packaging\DirectoryInterface|Packaging\FileInterface> */
    private array $files;
    /** @var string[] */
    private array $dependencies = [];

    public function __construct(
        private readonly string $outputPath,
        Packaging\DirectoryInterface|Packaging\FileInterface ...$files
    ) {
        $this->files = $files;
    }

    public function addTags(string ...$imageTags): self
    {
        array_push($this->imageTags, ...$imageTags);

        return $this;
    }

    public function withFile(Packaging\DirectoryInterface|Packaging\FileInterface ...$files): self
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
        if (false === $stream) {
            $error = error_get_last();
            throw new \ErrorException(
                $error['message'] ?? 'Failed to open output file',
                filename: $error['file'] ?? '',
                line: $error['line'] ?? 0
            );
        }
        stream_copy_to_stream($archive->asResource(), $stream);
        gzclose($stream);
    }
}
