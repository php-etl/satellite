<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\Pipeline;

use PhpParser\Builder;
use PhpParser\Node;

final class ConsoleRuntime implements Builder
{
    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\Pipeline\\Console'),
            args: [
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Symfony\\Component\\Console\\Output\\ConsoleOutput'),
                    )
                ),
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Pipeline'),
                        args: [
                            new Node\Arg(
                                new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\PipelineRunner'),
                                    args: [
                                        new Node\Arg(
                                            value: new Node\Expr\New_(
                                                class: new Node\Name\FullyQualified('Psr\\Log\\NullLogger'),
                                            )
                                        ),
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ],
        );
    }
}
