<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;

interface CloudContextInterface
{
    public function workingDirectory(): string;

    public function interpreter(): ExpressionLanguage;
}
