<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use Kiboko\Contract\Pipeline\PipelineInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class Workflow implements Builder
{
    private array $pipelines = [];

    public function __construct(
        private Node\Expr $runtime
    ) {
    }

    public function addPipeline(
        string $pipelineFilename,
    ): self {
        array_push($this->pipelines, function (Node\Expr $runtime) use ($pipelineFilename) {
            return new Node\Expr\MethodCall(
                var: $runtime,
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
        });

        return $this;
    }

    public function getNode(): Node
    {
        $workflow = $this->runtime;

        foreach ($this->pipelines as $pipeline) {
            $workflow = $pipeline($workflow);
        }

        return $workflow;
    }
}
