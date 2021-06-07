<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class Provider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return [
            new Env('env'),
            new File('file'),
            new Base64Decode('base64Decode'),
        ];
    }
}
