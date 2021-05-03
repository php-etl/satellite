<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Rejection implements Builder
{
    public function __construct(private ?Node\Expr $rejection = null)
    {
    }

    public function getNode(): Node\Stmt
    {
        return new Node\Stmt\Nop();
    }
}
