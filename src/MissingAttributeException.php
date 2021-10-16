<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

final class MissingAttributeException extends \RuntimeException
{
    /**
     * @param class-string $class
     */
    public static function expectedAttribute(string $class): self
    {
        return new self(sprintf(
            'The provided configuration object should have a %s attribute defined.',
            $class,
        ));
    }
}
