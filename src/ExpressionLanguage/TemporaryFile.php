<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class TemporaryFile extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            function (string $value): string {
                $pattern = <<<PHP
                    (function (\$data) {
                        if (!is_string(\$data)) {
                            return null;
                        }
                        \$stream = fopen('php://temp', 'r+');
                        fwrite(\$stream, \$data);
                        fseek(\$stream, 0, SEEK_SET);
                        return \$stream;
                    })(%s);
                    PHP;

                return \sprintf($pattern, $value);
            },
            function ($content) {
                if (!is_string($content)) {
                    return null;
                }
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, $content);
                fseek($stream, 0, SEEK_SET);
                return $stream;
            },
        );
    }
}
