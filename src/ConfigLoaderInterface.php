<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

interface ConfigLoaderInterface
{
    public function loadFile(string $file): array;
}
