<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Workflow implements Builder
{
    private array $jobs = [];

    public function __construct(
        private readonly Node\Expr $runtime
    ) {}

    public function addPipeline(string $code, string $pipelineFilename): self {
        $this->jobs[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('job'),
            args: [
                new Node\Arg(
                    new Node\Expr\StaticCall(
                        new Node\Name\FullyQualified(\Kiboko\Component\Workflow\JobCode::class),
                        new Node\Identifier('fromString'),
                        [
                            new Node\Arg(new Node\Scalar\String_($code)),
                        ],
                    ),
                ),
                new Node\Arg(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('runtime'),
                        name: 'loadPipeline',
                        args: [
                            new Node\Arg(
                                new Node\Expr\StaticCall(
                                    new Node\Name\FullyQualified(\Kiboko\Component\Workflow\JobCode::class),
                                    new Node\Identifier('fromString'),
                                    [
                                        new Node\Arg(new Node\Scalar\String_($code)),
                                    ],
                                ),
                            ),
                            new Node\Arg(
                                value: new Node\Expr\BinaryOp\Concat(
                                    left: new Node\Scalar\MagicConst\Dir(),
                                    right: new Node\Scalar\Encapsed(
                                        parts: [
                                            new Node\Scalar\EncapsedStringPart('/'),
                                            new Node\Scalar\EncapsedStringPart($pipelineFilename),
                                        ],
                                    ),
                                ),
                            ),
                        ],
                    ),
                ),
            ],
        );

        return $this;
    }

    public function addAction(string $code, string $pipelineFilename): self
    {
        $this->jobs[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('job'),
            args: [
                new Node\Arg(
                    new Node\Expr\StaticCall(
                        new Node\Name\FullyQualified(\Kiboko\Component\Workflow\JobCode::class),
                        new Node\Identifier('fromString'),
                        [
                            new Node\Arg(new Node\Scalar\String_($code)),
                        ],
                    ),
                ),
                new Node\Arg(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('runtime'),
                        name: 'loadAction',
                        args: [
                            new Node\Arg(
                                new Node\Expr\StaticCall(
                                    new Node\Name\FullyQualified(\Kiboko\Component\Workflow\JobCode::class),
                                    new Node\Identifier('fromString'),
                                    [
                                        new Node\Arg(new Node\Scalar\String_($code)),
                                    ],
                                ),
                            ),
                            new Node\Arg(
                                value: new Node\Expr\BinaryOp\Concat(
                                    left: new Node\Scalar\MagicConst\Dir(),
                                    right: new Node\Scalar\Encapsed(
                                        parts: [
                                            new Node\Scalar\EncapsedStringPart('/'),
                                            new Node\Scalar\EncapsedStringPart($pipelineFilename),
                                        ],
                                    ),
                                ),
                            ),
                        ],
                    ),
                ),
            ],
        );

        return $this;
    }

    public function getNode(): Node
    {
        $workflow = $this->runtime;

        foreach ($this->jobs as $job) {
            $workflow = $job($workflow);
        }

        return $workflow;
    }
}
