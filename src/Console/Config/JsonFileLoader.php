<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Config;

use Symfony\Component\Config;

final class JsonFileLoader extends Config\Loader\FileLoader
{
    public function load(mixed $resource, mixed $type = null): mixed
    {
        return json_decode(json: file_get_contents($resource), associative: true);
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return \is_string($resource)
            && 'json' === pathinfo($resource, \PATHINFO_EXTENSION);
    }
}
