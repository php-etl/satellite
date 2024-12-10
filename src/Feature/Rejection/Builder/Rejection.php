<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Rejection implements Builder
{
    public function __construct(
        private ?Node\Expr $rejection = null
    ) {
    }

    public function withRejection(Node\Expr $rejection): void
    {
        $this->rejection = $rejection;
    }

    private static function nullRejection(): Node\Expr
    {
        return new Node\Expr\New_(
            new Node\Name\FullyQualified('Kiboko\Contract\Pipeline\NullRejection')
        );
    }

    public function getNode(): Node\Expr
    {
        return $this->rejection ?? self::nullRejection();
    }
}
