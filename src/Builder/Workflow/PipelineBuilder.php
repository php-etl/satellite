<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\Workflow;

use PhpParser\Builder;
use PhpParser\Node;

final class PipelineBuilder implements Builder
{
    public function __construct(private Builder $builder)
    {
    }

    public function getNode(): Node\Stmt
    {
        return new Node\Stmt\Return_(
            new Node\Expr\Closure(
                subNodes: [
                    'static' => true,
                    'params' => [
                        new Node\Param(
                            var: new Node\Expr\Variable('runtime'),
                            type: new Node\Name\FullyQualified('Kiboko\\Component\\Satellite\\Console\\PipelineRuntimeInterface'),
                        )
                    ],
                    'stmts' => [
                        $this->builder->getNode(),
                        new Node\Stmt\Return_(
                            expr: new Node\Expr\Variable('runtime')
                        )
                    ]
                ]
            ),
        );
    }
}
