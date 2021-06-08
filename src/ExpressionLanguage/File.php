<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;

final class File extends ExpressionFunction
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            function (string $value): string {
                $pattern = <<<PHP
                    (function () {
                        \$resource = \\tmpfile();
                        if (\$resource === false) {
                            throw new \\RuntimeException('Could not open temporary file.');
                        }
    
                        \\fwrite(\$resource, %s);
                        \\fseek(\$resource, 0, \\SEEK_SET);
    
                        return \\stream_get_meta_data(\$resource)['uri'];
                    })();
                    PHP;

                return \sprintf($pattern, $value);
            },
            function (string $value): string {
                $resource = \tmpfile();
                if ($resource === false) {
                    throw new \RuntimeException('Could not open temporary file.');
                }

                \fwrite($resource, $value);
                \fseek($resource, 0, \SEEK_SET);

                return \stream_get_meta_data($resource)['uri'];
            },
        );
    }
}
