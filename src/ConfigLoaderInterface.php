<?php

namespace Kiboko\Component\Satellite;

interface ConfigLoaderInterface
{
    public function loadFile(string $file): array;
}
