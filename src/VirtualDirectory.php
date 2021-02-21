<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

final class VirtualDirectory implements DirectoryInterface
{
    private string $path;
    private \ArrayIterator $children;

    public function __construct()
    {
        $this->path = hash('sha512', random_bytes(64)).'.temp';
        $this->children = new \ArrayIterator();
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function add(FileInterface|DirectoryInterface ...$files): self
    {
        foreach ($files as $file) {
            $this->children->append($file);
        }

        return $this;
    }

    public function hasChildren()
    {
        return $this->current() instanceof DirectoryInterface;
    }

    public function getChildren()
    {
        return $this->current();
    }

    public function current()
    {
        return $this->children->current();
    }

    public function next()
    {
        $this->children->next();
    }

    public function key()
    {
        return $this->children->key();
    }

    public function valid()
    {
        return $this->children->valid();
    }

    public function rewind()
    {
        $this->children->rewind();
    }
}
