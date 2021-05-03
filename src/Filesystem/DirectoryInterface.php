<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Filesystem;

interface DirectoryInterface extends \RecursiveIterator
{
    public function getPath(): string;
}
