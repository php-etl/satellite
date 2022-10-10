<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class Env extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            fn ($value) => sprintf('getenv(%s)', $value),
            fn ($value) => getenv($value),
        );
    }
}
