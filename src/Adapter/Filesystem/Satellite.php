<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Satellite\Adapter\Composer;
use Kiboko\Contract\Packaging;
use Kiboko\Component\Satellite\SatelliteInterface;
use Psr\Log\LoggerInterface;

final class Satellite implements SatelliteInterface
{
    /** @var iterable<Packaging\DirectoryInterface|Packaging\FileInterface> */
    private iterable $files;
    private iterable $dependencies;

    public function __construct(
        private string $workdir,
        private Composer $composer,
        Packaging\FileInterface|Packaging\DirectoryInterface ...$files
    ) {
        $this->workdir = $workdir;
        $this->files = $files;
        $this->dependencies = [];
    }

    public function withFile(Packaging\FileInterface|Packaging\DirectoryInterface ...$files): self
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
        LoggerInterface $logger
    ): void {
        foreach ($this->files as $file) {
            if ($file instanceof Packaging\DirectoryInterface) {
                foreach (new \RecursiveIteratorIterator($file) as $current) {
                    $stream = fopen($this->workdir.'/'.$current->getPath(), 'wb');
                    stream_copy_to_stream($current->asResource(), $stream);
                    fclose($stream);
                }
            } else {
                $stream = fopen($this->workdir.'/'.$file->getPath(), 'wb');
                stream_copy_to_stream($file->asResource(), $stream);
                fclose($stream);
            }
        }

        $this->composer->require(...$this->dependencies);
    }
}
