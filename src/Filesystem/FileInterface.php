<?php

namespace Kiboko\Component\Satellite\Filesystem;

interface FileInterface extends AssetInterface
{
    public function getPath(): string;
}
