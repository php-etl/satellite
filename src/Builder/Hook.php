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
        return new Node\Stmt\Return_(
            new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('psr17Factory'),
                    'createResponse',
                    [
                        new Node\Arg(
                            new Node\Scalar\DNumber(200),
                        )
                    ]
                ),
                'withBody',
                [
                    new Node\Arg(
                        new Node\Expr\MethodCall(
                            new Node\Expr\Variable('psr17Factory'),
                            'createStream',
                            [
                                new Node\Arg(
                                    new Node\Expr\FuncCall(
                                        new Node\Name('json_encode'),
                                        [
                                            new Node\Arg(
                                                new Node\Expr\Array_(
                                                    [
                                                        new Node\Expr\ArrayItem(
                                                            new Node\Scalar\String_('Hello world'),
                                                            new Node\Scalar\String_('message')
                                                        ),
                                                        new Node\Expr\ArrayItem(
                                                            new Node\Expr\FuncCall(new Node\Name('gethostname')),
                                                            new Node\Scalar\String_('server')
                                                        ),
                                                    ],
                                                    [
                                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                                    ]
                                                )
                                            )
                                        ]
                                    )
                                )
                            ]
                        )
                    )
                ]
            )
        );
    }
}
