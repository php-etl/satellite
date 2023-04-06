<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class Interpreter
{
    /** @var list<ExpressionFunctionProviderInterface|ExpressionFunction> */
    private readonly array $expressionFunctions;

    public function __construct(
        ExpressionFunctionProviderInterface|ExpressionFunction ...$expressionFunctions
    ) {
        $this->expressionFunctions = $expressionFunctions;
    }

    public function context(): ExpressionLanguage
    {
        $context = new ExpressionLanguage();

        foreach ($this->expressionFunctions as $functionOrProvider) {
            if ($functionOrProvider instanceof ExpressionFunctionProviderInterface) {
                $context->registerProvider($functionOrProvider);
            } elseif ($functionOrProvider instanceof ExpressionFunction) {
                $context->addFunction($functionOrProvider);
            }
        }

        return $context;
    }

    public function isolate(): self
    {
        return clone $this;
    }
}
