<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class InheritBuilder implements Builder
{
    public function getNode(): Node\Expr
    {
        return new Node\Expr\Variable('logger');
    }
}
