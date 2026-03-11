<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class PluginNotFoundException extends \RuntimeException
{
    public static function gyroscopsPlugins(): self
    {
        return new self('Could not load Gyroscops Satellite plugins.');
    }
}
