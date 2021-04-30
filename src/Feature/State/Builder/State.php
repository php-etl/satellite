<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class State implements Builder
{
    public function __construct(private ?Node\Expr $state = null)
    {
    }

    public function getNode(): Node\Stmt
    {
        return new Node\Stmt\Nop();
    }
}
