<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Action implements Builder
{
    private array $actions = [];

    public function __construct(
        private Node\Expr $runtime
    ) {
    }

    public function addAction(
        string $pipelineFilename,
    ): self {
        array_push($this->actions, function (Node\Expr $runtime) use ($pipelineFilename) {
            return new Node\Expr\MethodCall(
                var: $runtime,
                name: new Node\Identifier('job'),
                args: [
                    new Node\Arg(
                        new Node\Expr\MethodCall(
                            var: new Node\Expr\Variable('runtime'),
                            name: 'loadAction',
                            args: [
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
        });

        return $this;
    }

    public function getNode(): Node
    {
        $workflow = $this->runtime;

        foreach ($this->actions as $action) {
            $workflow = $action($workflow);
        }

        return $workflow;
    }
}
