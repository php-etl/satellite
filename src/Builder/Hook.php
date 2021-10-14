<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Hook implements Builder
{
    public function __construct(
        private array $pipeline
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
        /*

        return $psr17Factory->createResponse(200)
            ->withBody(
                $psr17Factory->createStream(
                    json_encode([
                        'message' => 'Hello World!',
                        'server' => gethostname(),
                    ])
                )
            );
         */
        return new Node\Expr\Include_(
            new Node\Scalar\String_($this->pipeline['http_hook']['function']),
            Node\Expr\Include_::TYPE_REQUIRE,
        );
    }
}
