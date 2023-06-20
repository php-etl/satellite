<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class Provider implements ExpressionFunctionProviderInterface
{
    public function getFunctions(): array
    {
        return [
            new Env('env'),
            new EnvAsFile('envAsFile'),
            new File('file'),
            new Base64Decode('base64Decode'),
            new TemporaryFile('temporaryFile'),
            new InSql('inSql'),
        ];
    }
}
