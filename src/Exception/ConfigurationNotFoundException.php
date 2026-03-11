<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class ConfigurationNotFoundException extends \RuntimeException
{
    public static function fileNotFound(): self
    {
        return new self('Could not find configuration file.');
    }
}
