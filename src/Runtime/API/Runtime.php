<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\API;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;
use Kiboko\Contract\Configurator;

final class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(private array $config, private string $filename = 'api.php')
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(
        Configurator\FactoryInterface $service,
        Satellite\SatelliteInterface $satellite,
        LoggerInterface $logger
    ): void {
        $repository = $service->compile($this->config);

        $satellite->withFile(
            new Packaging\File($this->filename, new Packaging\Asset\InMemory(
                '<?php' . PHP_EOL . (new PrettyPrinter\Standard())->prettyPrint($this->build($repository->getBuilder()))
            )),
        );

        $satellite->withFile(
            ...$repository->getFiles(),
        );

        $satellite->dependsOn(...$repository->getPackages());
    }

    public function build(Builder $builder): array
    {
        return [
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('FastRoute'), 'NikiFastRoute')]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\FastRoute'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\Utils\\Dispatcher'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\Uuid'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\BasePath'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\RequestHandler'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7Server'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Laminas\\HttpHandlerRunner\\Emitter\\SapiEmitter'))]),

            new Node\Stmt\Return_(
                new Node\Expr\Closure(
                    subNodes: [
                        'static' => true,
                        'params' => [
                            new Node\Param(
                                var: new Node\Expr\Variable('runtime'),
                                type: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\API\\APIRuntime'),
                            ),
                        ],
                        'stmts' => $this->buildAPIClosure($builder)
                    ]
                ),
            )
        ];
    }

    public function buildAPIClosure(Builder $builder): array
    {
        return [
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
                        new Node\Name('NikiFastRoute\\simpleDispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Closure([
                                    'params' => [
                                        new Node\Param(
                                            new Node\Expr\Variable('router'),
                                            null,
                                            new Node\Name('NikiFastRoute\\RouteCollector')
                                        )
                                    ],
                                    'uses' => [
                                        new Node\Expr\Variable('runtime'),
                                        new Node\Expr\Variable('psr17Factory'),
                                    ],
                                    'stmts' => iterator_to_array($this->compileRoutes(
                                        $builder,
                                        new Node\Expr\Variable('router'),
                                        new Node\Expr\Variable('psr17Factory'),
                                    )),
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
                        new Node\Name('Dispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Array_(
                                    [
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Uuid'),
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('BasePath'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Scalar\String_($this->config['path'] ?? '/')
                                                    )
                                                ],
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            (new Satellite\Runtime\Authorization())->build($this->config['http_api']),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('FastRoute'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Expr\Variable('fastRouteDispatcher')
                                                    )
                                                ]
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('RequestHandler'),
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

    private function compileRoutes(Builder $builder, Node\Expr\Variable $router, Node\Expr\Variable $factory): \Iterator
    {
        foreach ($this->config['http_api']['routes'] as $route) {
            yield new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    $router,
                    $routeConfig['method'] ?? 'post',
                    [
                        new Node\Arg(
                            new Node\Scalar\String_($this->config['http_api']['path'] . $route['route'])
                        ),
                        new Node\Arg(
                            $this->compileClosure($builder, $route, $router, $factory)
                        )
                    ]
                )
            );
        }
    }

    private function compileClosure(
        Builder $builder,
        array $routeConfig,
        Node\Expr\Variable $router,
        Node\Expr\Variable $factory
    ) {
        return new Node\Expr\Closure(
            subNodes: [
                'params' => [
                    new Node\Param(
                        var: new Node\Expr\Variable('request'),
                        type: new Node\Name\FullyQualified('Psr\Http\Message\RequestInterface'),
                    )
                ],
                'uses' => [
                    new Node\Expr\Variable('runtime'),
                    new Node\Expr\Variable('psr17Factory'),
                ],
                'stmts' => [
                    new Node\Stmt\Expression(
                        expr: new Node\Expr\Assign(
                            var: new Node\Expr\Variable('interpreter'),
                            expr: new Node\Expr\New_(new Node\Name('Symfony\Component\ExpressionLanguage\ExpressionLanguage')),
                        ),
                    ),
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('items'),
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('interpreter'),
                                'evaluate',
                                [
                                    new Node\Arg(
                                        new Node\Scalar\String_($routeConfig['expression'])
                                    ),
                                    new Node\Arg(
                                        new Node\Expr\FuncCall(
                                            name: new Node\Name('json_decode'),
                                            args: [
                                                new Node\Arg(
                                                    value: new Node\Expr\MethodCall(
                                                        var: new Node\Expr\MethodCall(
                                                            var: new Node\Expr\Variable('request'),
                                                            name: 'getBody',
                                                        ),
                                                        name: 'getContents'
                                                    )
                                                ),
                                                new Node\Arg(
                                                    new Node\Expr\ConstFetch(new Node\Name('true'))
                                                )
                                            ]
                                        )
                                    )
                                ]
                            )
                        )
                    ),
                    new Node\Stmt\Foreach_(
                        new Node\Expr\Variable('items'),
                        new Node\Expr\Variable('item'),
                        [
                            'stmts' => [
                                new Node\Stmt\Expression(
                                    new Node\Expr\MethodCall(
                                        var: new Node\Expr\Variable('runtime'),
                                        name: 'feed',
                                        args: [
                                            new Node\Arg(
                                                new Node\Scalar\String_($routeConfig['route'])
                                            ),
                                            new Node\Arg(
                                                value: new Node\Expr\Variable('item'),
                                                unpack: true
                                            )
                                        ]
                                    ),
                                ),
                            ]
                        ]
                    ),
                    new Node\Stmt\Expression(
                        expr: new Node\Expr\Assign(
                            var: new Node\Expr\Variable('response'),
                            expr: new Node\Expr\MethodCall(
                                var: new Node\Expr\Variable('runtime'),
                                name: 'run',
                                args: [
                                    new Node\Arg(
                                        new Node\Scalar\String_($routeConfig['route']))
                                ]
                            )
                        )
                    ),
                    $builder->getNode()
                ]
            ],
        );
    }
}
