<?php

namespace Kiboko\Component\Satellite\Adapter\Docker;

interface FileInterface extends AssetInterface
{
    public function getPath(): string;
}
