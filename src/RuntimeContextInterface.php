<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;

interface RuntimeContextInterface
{
    public function workingDirectory(): string;

    public function interpreter(): ExpressionLanguage;
}
