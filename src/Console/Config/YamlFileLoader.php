<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Config;

use Symfony\Component\Config;
use Symfony\Component\Yaml;

final class YamlFileLoader extends Config\Loader\FileLoader
{
    public function load(mixed $resource, mixed $type = null): mixed
    {
        return Yaml\Yaml::parse(input: file_get_contents($resource));
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return \is_string($resource)
            && preg_match('/ya?ml/i', pathinfo($resource, \PATHINFO_EXTENSION));
    }
}
