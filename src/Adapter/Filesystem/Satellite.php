<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Satellite\Filesystem\DirectoryInterface;
use Kiboko\Component\Satellite\Filesystem\FileInterface;
use Kiboko\Component\Satellite\SatelliteInterface;
use Psr\Log\LoggerInterface;

final class Satellite implements SatelliteInterface
{
    private string $workdir;
    /** @var iterable<DirectoryInterface|FileInterface> */
    private iterable $files;
    private iterable $dependencies;

    public function __construct(
        string $workdir,
        FileInterface|DirectoryInterface ...$files
    ) {
        $this->workdir = $workdir;
        $this->files = $files;
        $this->dependencies = [];
    }

    public function withFile(DirectoryInterface|FileInterface ...$files): self
    {
        array_push($this->files, ...$files);

        return $this;
    }

    public function dependsOn(string ...$dependencies): self
    {
        array_push($this->dependencies, ...$dependencies);

        return $this;
    }

    public function build(LoggerInterface $logger): void
    {
        foreach ($this->files as $file) {
            if ($file instanceof DirectoryInterface) {
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
    }
}
