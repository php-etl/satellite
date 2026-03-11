<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class InvalidAutoloadTypeException extends \InvalidArgumentException
{
    public static function unknownType(string $type): self
    {
        return new self(\sprintf('Unknown autoload type: %s', $type));
    }
}
