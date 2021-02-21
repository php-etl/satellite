<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;

final class Directory implements Satellite\DirectoryInterface
{
    private string $path;
    private \RecursiveIterator $iterator;

    public function __construct(string $sourcePath)
    {
        $this->path = $sourcePath;
        $this->iterator = new \RecursiveDirectoryIterator(
            $sourcePath,
            \RecursiveDirectoryIterator::SKIP_DOTS
            | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            | \RecursiveDirectoryIterator::CURRENT_AS_FILEINFO
            | \RecursiveDirectoryIterator::KEY_AS_PATHNAME
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function hasChildren()
    {
        return $this->iterator->hasChildren();
    }

    public function getChildren()
    {
        $child = clone $this;
        $child->iterator = $this->iterator->getChildren();
        return $child;
    }

    public function current()
    {
        $current = $this->iterator->current();
        if ($current->isDir()) {
            return new self($current->getPathname());
        }

        return new Satellite\File(
            $current->getPathname(),
            new Satellite\Asset\LocalFile($current->getPathname())
        );
    }

    public function next()
    {
        $this->iterator->next();
    }

    public function key()
    {
        return $this->iterator->key();
    }

    public function valid()
    {
        return $this->iterator->valid();
    }

    public function rewind()
    {
        $this->iterator->rewind();
    }
}
