<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\API;

use PhpParser\Node;

final class APIRuntime
{
    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\API\\APIRuntime'),
            args: [
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Pipeline'),
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\PipelineRunner'),
                                    args: [
                                        new Node\Arg(
                                            value: new Node\Expr\New_(
                                                class: new Node\Name\FullyQualified('Psr\\Log\\NullLogger')
                                            )
                                        ),
                                    ]
                                )
                            ),
                        ]
                    ),
                ),
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('ProjectServiceContainer')
                    ),
                ),
            ]
        );
    }
}
