<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class Base64Decode extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            fn ($value) => sprintf('base64_decode(%s)', $value),
            fn ($value) => base64_decode((string) $value),
        );
    }
}
