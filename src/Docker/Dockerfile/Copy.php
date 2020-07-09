<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Docker\Dockerfile;

use Kiboko\Component\ETL\Satellite\Docker\File;

final class Copy implements LayerInterface
{
    private string $source;
    private string $destination;

    public function __construct(string $source, string $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    /** @return \Iterator|self[] */
    public static function directory(string $sourcePath, string $destinationPath): \Iterator
    {
        $iterator = new \RecursiveDirectoryIterator($sourcePath,
            \RecursiveDirectoryIterator::SKIP_DOTS
            | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
            | \RecursiveDirectoryIterator::KEY_AS_PATHNAME
        );

        /** @var \SplFileInfo $fileInfo */
        foreach (new \RecursiveIteratorIterator($iterator) as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            yield new self(
                preg_replace('/^'.preg_quote($sourcePath, '/').'/', '', $fileInfo->getPathname()),
                preg_replace('/^'.preg_quote($sourcePath, '/').'/', $destinationPath, $fileInfo->getPathname()),
            );
        }
    }

    public function __toString()
    {
        return sprintf('COPY %s %s', $this->source, $this->destination);
    }
}