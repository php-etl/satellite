<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class API implements Builder
{
    public function getNode(): Node
    {
        return new Node\Stmt\Return_(
            new Node\Expr\MethodCall(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('psr17Factory'),
                    'createResponse',
                    [
                        new Node\Arg(
                            new Node\Scalar\LNumber(200),
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
                                                            new Node\Expr\Variable('response'),
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
