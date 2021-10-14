<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\Hook;

use PhpParser\Builder;
use PhpParser\Node;

final class PipelineBuilder
{
public function __construct(private Builder $builder)
    {
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\Closure(
            subNodes: [
                'static' => true,
                'params' => [
                    new Node\Param(
                        var: new Node\Expr\Variable('runtime'),
                        type: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\HookRuntimeInterface'),
                    )
                ],
                'stmts' => [
                    new Node\Stmt\Expression(
                        $this->builder->getNode()
                    ),
                    new Node\Stmt\Return_(
                        expr: new Node\Expr\Variable('runtime')
                    )
                ]
            ]
        );
    }
}
