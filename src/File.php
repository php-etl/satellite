<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

final class File implements FileInterface
{
    private string $path;
    private AssetInterface $content;

    public function __construct(string $path, AssetInterface $content)
    {
        $this->path = $path;
        $this->content = $content;
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
