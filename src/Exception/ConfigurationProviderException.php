<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Exception;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class ConfigurationProviderException extends \InvalidArgumentException
{
    public static function mustImplementProviderInterface(string $providerClass): self
    {
        return new self(
            \sprintf(
                'Provider class "%s" must implement %s.',
                $providerClass,
                ExpressionFunctionProviderInterface::class
            )
        );
    }
}
