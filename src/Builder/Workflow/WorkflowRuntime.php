<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\Workflow;

use PhpParser\Builder;
use PhpParser\Node;

final class WorkflowRuntime implements Builder
{
    public function getNode(): Node
    {
        return new Node\Stmt\Expression(
            new Node\Expr\New_(
                class: new Node\Name\FullyQualified('Kiboko\\Component\\Satellite\\Console\\WorkflowConsoleRuntime'),
                args: [
                    new Node\Arg(
                        value: new Node\Expr\New_(
                            class: new Node\Name\FullyQualified('Symfony\\Component\\Console\\Output\\ConsoleOutput'),
                        )
                    ),
                    new Node\Arg(
                        value: new Node\Expr\New_(
                            class: new Node\Name\FullyQualified('Kiboko\\Component\\Workflow\\Workflow'),
                        ),
                    ),
                    new Node\Arg(
                        new Node\Expr\New_(
                            class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\PipelineRunner'),
                            args: [
                                new Node\Arg(
                                    value: new Node\Expr\New_(
                                        class: new Node\Name\FullyQualified('Psr\\Log\\NullLogger'),
                                    )
                                )
                            ],
                        ),
                    ),
                ],
            )
        );
    }
}