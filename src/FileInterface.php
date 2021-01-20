<?php

namespace Kiboko\Component\Satellite;

interface FileInterface extends AssetInterface
{
    public function getPath(): string;
}
