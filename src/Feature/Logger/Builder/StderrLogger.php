<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class StderrLogger implements Builder
{
    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'extends' => new Node\Name\FullyQualified('Psr\\Log\\AbstractLogger'),
                    'stmts' => [
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier(name: 'log'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'params' => [
                                        new Node\Param(
                                            var: new Node\Expr\Variable(name: 'level'),
                                        ),
                                        new Node\Param(
                                            var: new Node\Expr\Variable(name: 'message'),
                                        ),
                                        new Node\Param(
                                            var: new Node\Expr\Variable(name: 'context'),
                                            default: new Node\Expr\Array_(
                                                attributes: [
                                                    'kind' => Node\Expr\Array_::KIND_SHORT,
                                                ],
                                            ),
                                            type: new Node\Name(name: 'array'),
                                        ),
                                    ],
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        new Node\Expr\FuncCall(
                                            name: new Node\Name\FullyQualified('file_put_contents'),
                                            args: [
                                                new Node\Arg(
                                                    value: new Node\Scalar\String_('php://stderr'),
                                                ),
                                                new Node\Arg(
                                                    value: new Node\Expr\BinaryOp\Concat(
                                                        left: new Node\Expr\FuncCall(
                                                            name: new Node\Name\FullyQualified('sprintf'),
                                                            args: [
                                                                new Node\Arg(
                                                                    value: new Node\Scalar\String_('[%s] %s'),
                                                                ),
                                                                new Node\Arg(
                                                                    value: new Node\Expr\Variable('level'),
                                                                ),
                                                                new Node\Arg(
                                                                    value: new Node\Expr\Variable('message'),
                                                                ),
                                                            ],
                                                        ),
                                                        right: new Node\Expr\ConstFetch(
                                                            name: new Node\Name\FullyQualified('PHP_EOL'),
                                                        ),
                                                    ),
                                                ),
                                            ],
                                        ),
                                    ),
                                ],
                            ],
                        ),
                    ],
                ],
            ),
        );
    }
}
