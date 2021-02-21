<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Config;

use Symfony\Component\Config;
use Symfony\Component\Yaml;

final class YamlFileLoader extends Config\Loader\FileLoader
{
    public function load($resource, $type = null)
    {
        return Yaml\Yaml::parse(input: file_get_contents($resource));
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource)
            && preg_match('/ya?ml/i', pathinfo($resource, PATHINFO_EXTENSION));
    }
}
