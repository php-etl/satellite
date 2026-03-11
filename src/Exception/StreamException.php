<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class StreamException extends \RuntimeException
{
    public static function couldNotOpen(string $resource): self
    {
        return new self(\sprintf('Could not open stream: %s', $resource));
    }
}
