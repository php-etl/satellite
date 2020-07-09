<?php

namespace Kiboko\Component\ETL\Satellite\Docker;

interface FileInterface extends AssetInterface
{
    public function getPath(): string;
}