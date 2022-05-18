<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Config;

use Symfony\Component\Config;

final class JsonFileLoader extends Config\Loader\FileLoader
{
    public function load($resource, $type = null)
    {
        return json_decode(json: file_get_contents($resource), associative: true);
    }

    public function supports($resource, $type = null)
    {
        return \is_string($resource)
            && 'json' === pathinfo($resource, \PATHINFO_EXTENSION);
    }
}
