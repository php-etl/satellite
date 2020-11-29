<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Runtime\Http;

use Kiboko\Component\ETL\Satellite\Runtime\RuntimeInterface;
use PhpParser\Node;
use PhpParser\Builder;

final class Api implements RuntimeInterface
{
    public function build(): array
    {
        return [
            new Node\Stmt\Namespace_(new Node\Name('Foo')),
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('FastRoute'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7Server'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Psr'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Laminas\\HttpHandlerRunner\\Emitter\\SapiEmitter'))]),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('psr17Factory'),
                    new Node\Expr\New_(new Node\Name('Psr7\\Factory\\Psr17Factory')),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('creator'),
                    new Node\Expr\New_(
                        new Node\Name('Psr7Server\\ServerRequestCreator'),
                        [
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                        ]
                    ),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('request'),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('creator'),
                        'fromGlobals',
                    ),
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('fastRouteDispatcher'),
                    new Node\Expr\FuncCall(
                        new Node\Name('FastRoute\\simpleDispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Closure([
                                    'params' => [
                                        new Node\Param(
                                             new Node\Expr\Variable('router'),
                                            null,
                                            new Node\Name('FastRoute\\RouteCollector')
                                        )
                                    ],
                                    'uses' => [
                                        new Node\Expr\Variable('psr17Factory')
                                    ],
                                    'stmts' => $this->compileRoutes(
                                        new Node\Expr\Variable('router'),
                                        new Node\Expr\Variable('psr17Factory'),
                                    ),
                                ])
                            ),
                        ]
                    )
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('dispatcher'),
                    new Node\Expr\New_(
                        new Node\Name('Middlewares\\Utils\\Dispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Array_(
                                    [
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\FastRoute'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Expr\Variable('fastRouteDispatcher')
                                                    )
                                                ]
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\RequestHandler'),
                                            ),
                                        ),
                                    ],
                                    [
                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\New_(
                        new Node\Name('SapiEmitter')
                    ),
                    'emit',
                    [
                        new Node\Arg(
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('dispatcher'),
                                'dispatch',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('request')
                                    )
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ];
    }

    private function compileRoutes(Node\Expr\Variable $router, Node\Expr\Variable $factory): array
    {
        return [
            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    $router,
                    'get',
                    [
                        new Node\Arg(
                            new Node\Scalar\String_('/hello')
                        ),
                        new Node\Arg(
                            new Node\Expr\Closure(
                                [
                                    'params' => [
                                        new Node\Param(
                                            new Node\Expr\Variable('request'),
                                            null,
                                            new Node\Name('Psr\Http\Message\ServerRequestInterface')
                                        )
                                    ],
                                    'uses' => [
                                        $factory
                                    ],
                                    'stmts' => [
                                        new Node\Stmt\Return_(
                                            new Node\Expr\MethodCall(
                                                new Node\Expr\MethodCall(
                                                    $factory,
                                                    'createResponse',
                                                    [
                                                        new Node\Arg(
                                                            new Node\Scalar\LNumber(200)
                                                        )
                                                    ]
                                                ),
                                                'withBody',
                                                [
                                                    new Node\Arg(
                                                        new Node\Expr\StaticCall(
                                                            new Node\Name('Psr7\Stream'),
                                                            'create',
                                                            [
                                                                new Node\Arg(
                                                                    new Node\Expr\FuncCall(
                                                                        new Node\Name('json_encode'),
                                                                        [
                                                                            new Node\Arg(
                                                                                new Node\Expr\Array_(
                                                                                    [
                                                                                        new Node\Expr\ArrayItem(
                                                                                            new Node\Scalar\String_('Hello World!'),
                                                                                            new Node\Scalar\String_('message'),
                                                                                        ),
                                                                                        new Node\Expr\ArrayItem(
                                                                                            new Node\Expr\FuncCall(new Node\Name('gethostname')),
                                                                                            new Node\Scalar\String_('server'),
                                                                                        ),
                                                                                    ],
                                                                                ),
                                                                            ),
                                                                        ],
                                                                    ),
                                                                ),
                                                            ],
                                                        ),
                                                    ),
                                                ],
                                            ),
                                        ),
                                    ],
                                ],
                            )
                        ),
                    ],
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    $router,
                    'get',
                    [
                        new Node\Arg(
                            new Node\Scalar\String_('/events/products')
                        ),
                        new Node\Arg(
                            new Node\Expr\Closure(
                                [
                                    'params' => [
                                        new Node\Param(
                                            new Node\Expr\Variable('request'),
                                            null,
                                            new Node\Name('Psr\Http\Message\ServerRequestInterface')
                                        )
                                    ],
                                    'uses' => [
                                        $factory
                                    ],
                                    'stmts' => [
                                        new Node\Stmt\Return_(
                                            new Node\Expr\MethodCall(
                                                new Node\Expr\MethodCall(
                                                    $factory,
                                                    'createResponse',
                                                    [
                                                        new Node\Arg(
                                                            new Node\Scalar\LNumber(200)
                                                        )
                                                    ]
                                                ),
                                                'withBody',
                                                [
                                                    new Node\Arg(
                                                        new Node\Expr\StaticCall(
                                                            new Node\Name('Psr7\Stream'),
                                                            'create',
                                                            [
                                                                new Node\Arg(
                                                                    new Node\Expr\FuncCall(
                                                                        new Node\Name('json_encode'),
                                                                        [
                                                                            new Node\Arg(
                                                                                new Node\Expr\Array_(
                                                                                    [
                                                                                        new Node\Expr\ArrayItem(
                                                                                            new Node\Scalar\String_('Ok'),
                                                                                            new Node\Scalar\String_('status'),
                                                                                        ),
                                                                                        new Node\Expr\ArrayItem(
                                                                                            new Node\Expr\FuncCall(new Node\Name('gethostname')),
                                                                                            new Node\Scalar\String_('server'),
                                                                                        ),
                                                                                    ],
                                                                                ),
                                                                            ),
                                                                        ],
                                                                    ),
                                                                ),
                                                            ],
                                                        ),
                                                    ),
                                                ],
                                            ),
                                        ),
                                    ],
                                ],
                            )
                        ),
                    ],
                ),
            ),
        ];
    }
}
