<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class API implements Builder
{
    public function __construct(
        private Node\Expr $runtime
    ) {
    }

    public function addPipeline(string $pipelineFilename): self
    {
        $this->pipelines = new Node\Expr\MethodCall(
            var: $this->runtime,
            name: new Node\Identifier('job'),
            args: [
                new Node\Arg(
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('runtime'),
                        name: 'loadPipeline',
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\BinaryOp\Concat(
                                    left: new Node\Scalar\MagicConst\Dir(),
                                    right: new Node\Scalar\Encapsed(
                                        parts: [
                                            new Node\Scalar\EncapsedStringPart('/'),
                                            new Node\Scalar\EncapsedStringPart($pipelineFilename)
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
        return new Node\Stmt\Expression($this->runtime);
    }
}
