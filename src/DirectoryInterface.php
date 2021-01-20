<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

interface DirectoryInterface extends \RecursiveIterator
{
    public function getPath(): string;
}
