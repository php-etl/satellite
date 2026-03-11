<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class WorkingDirectoryException extends \RuntimeException
{
    public static function couldNotGet(): self
    {
        return new self('Could not get current working directory.');
    }
}
