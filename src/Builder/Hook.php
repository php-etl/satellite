<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Hook implements Builder
{
    public function getNode(): Node
    {
        return new Node\Expr\Closure(subNodes: [
            'params' => [
                new Node\Param(
                    var: new Node\Expr\Variable('request')
                ),
            ],
        ]);
    }
}
