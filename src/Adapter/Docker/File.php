<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker;

final class File implements FileInterface
{
    private string $path;
    private AssetInterface $content;

    public function __construct(string $path, AssetInterface $content)
    {
        $this->path = $path;
        $this->content = $content;
    }

    /** @return \Iterator|self[] */
    public static function directory(string $sourcePath): \Iterator
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
                new Asset\File($fileInfo->getPathname())
            );
        }
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function asResource()
    {
        return $this->content->asResource();
    }
}