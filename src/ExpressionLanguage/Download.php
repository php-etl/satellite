<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class Download extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            function (string $value, string $context = ''): string {
                $pattern = <<<'PHP'
                    (function () use($input) {
                        $resource = \fopen(%s%s, 'r');
                        if ($resource === false) {
                            throw new \RuntimeException('Could not open file.');
                        }
                        return $resource;
                    })()
                    PHP;

                return sprintf($pattern, $value, $context);
            },
            function (string $value, string $context = '') {
                $resource = fopen($context.$value, 'r');
                if (false === $resource) {
                    throw new \RuntimeException('Could not open file.');
                }

                return $resource;
            },
        );
    }
}
