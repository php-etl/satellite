<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console;

use Kiboko\Component\Satellite\Cloud\CloudContextInterface;
use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;

class CloudContext implements CloudContextInterface
{
    public function __construct(
        private string $workingDirectory,
        private ExpressionLanguage $expressionLanguage,
    ) {
    }

    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function interpreter(): ExpressionLanguage
    {
        return clone $this->expressionLanguage;
    }
}
