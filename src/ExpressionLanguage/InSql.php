<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class InSql extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            function (string $path, string $name): string {
                $pattern = <<<'PHP'
                    (function () use ($input) {
                        $parameters = [];
                        foreach (%s as $key => $value) {
                            $parameters[] = ':' . %s . '_' . $key;
                        } 
                        
                        return 'IN (' . implode(', ', $parameters) . ')';
                    })()
                    PHP;

                return sprintf($pattern, $path, $name);
            },
            function (array $path, string $name) {
                $parameters = [];
                foreach ($path as $key => $value) {
                    $parameters[] = ':'.$name.'_'.$key;
                }

                return 'IN ('.implode(', ', $parameters).')';
            },
        );
    }
}
