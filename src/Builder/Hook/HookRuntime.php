<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder\Hook;

use PhpParser\Node;

final class HookRuntime
{
    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\Hook\\HookRuntime'),
            args: [
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified(\Kiboko\Component\Pipeline\Pipeline::class),
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified(\Kiboko\Component\Pipeline\PipelineRunner::class),
                                    args: [
                                        new Node\Arg(
                                            value: new Node\Expr\New_(
                                                class: new Node\Name\FullyQualified(\Psr\Log\NullLogger::class)
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