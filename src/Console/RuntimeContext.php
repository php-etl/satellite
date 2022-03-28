<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;
use Kiboko\Component\Satellite\RuntimeContextInterface;

final class RuntimeContext implements RuntimeContextInterface
{
    public function __construct(
        private string $workingDirectory,
        private ExpressionLanguage $expressionLanguage,
    ) {}

    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function interpreter(): ExpressionLanguage
    {
        return clone $this->expressionLanguage;
    }
}
