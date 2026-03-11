<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

final class ComposerException extends \RuntimeException
{
    public static function couldNotRead(string $file): self
    {
        return new self(\sprintf('Could not read %s', $file));
    }
}
