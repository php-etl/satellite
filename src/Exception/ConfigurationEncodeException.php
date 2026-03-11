<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class ConfigurationEncodeException extends \RuntimeException
{
    public static function failed(): self
    {
        return new self('Failed to encode configuration.');
    }
}
