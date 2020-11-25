<?php

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker;

interface FileInterface extends AssetInterface
{
    public function getPath(): string;
}