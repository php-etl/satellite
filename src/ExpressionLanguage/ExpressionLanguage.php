<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\ExpressionLanguage;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage as BaseExpressionLanguage;

final class ExpressionLanguage extends BaseExpressionLanguage
{
    public function __construct(?CacheItemPoolInterface $cache = null, array $providers = [])
    {
        parent::__construct($cache, [
            ...$providers,
            new Provider(),
        ]);
    }
}
