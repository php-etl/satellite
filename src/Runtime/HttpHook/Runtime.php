<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;

final readonly class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(private array $config, private string $filename = 'hook.php')
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(Configurator\FactoryInterface $service, Configurator\SatelliteInterface $satellite, LoggerInterface $logger): void
    {
        $repository = $service->compile($this->config);

        $satellite->withFile(
            new Packaging\File($this->filename, new Packaging\Asset\InMemory(
                '<?php'.\PHP_EOL.(new PrettyPrinter\Standard())->prettyPrint($this->build($repository->getBuilder()))
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
            ...$this->buildAutoload(),
            new Node\Stmt\Return_(
                new Node\Expr\Closure(
                    subNodes: [
                        'static' => true,
                        'params' => [
                            new Node\Param(
                                var: new Node\Expr\Variable('runtime'),
                                type: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\Hook\\HookRuntime'),
                            ),
                        ],
                        'stmts' => $this->buildHookClosure($builder),
                    ]
                ),
            ),
        ];
    }

    private function buildAutoload()
    {
        return [
            new Node\Stmt\Expression(
                expr: new Node\Expr\Include_(
                    expr: new Node\Expr\BinaryOp\Concat(
                        left: new Node\Scalar\MagicConst\Dir(),
                        right: new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    type: Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\BasePath'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\Utils\\Dispatcher'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\Uuid'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7Server'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Laminas\\HttpHandlerRunner\\Emitter\\SapiEmitter'))]),
        ];
    }

    private function buildHookClosure(Builder $builder)
    {
        return [
            new Node\Stmt\Expression(
                expr: new Node\Expr\Assign(
                    var: new Node\Expr\Variable('psr17Factory'),
                    expr: new Node\Expr\New_(new Node\Name('Psr7\\Factory\\Psr17Factory')),
                ),
            ),
            new Node\Stmt\Expression(
                expr: new Node\Expr\Assign(
                    var: new Node\Expr\Variable('creator'),
                    expr: new Node\Expr\New_(
                        class: new Node\Name('Psr7Server\\ServerRequestCreator'),
                        args: [
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                        ]
                    ),
                ),
            ),
            new Node\Stmt\Expression(
                expr: new Node\Expr\Assign(
                    var: new Node\Expr\Variable('request'),
                    expr: new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('creator'),
                        name: 'fromGlobals',
                    ),
                ),
            ),
            $this->buildDispatcher($builder),
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
                                    ),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ];
    }

    private function buildDispatcher(Builder $builder)
    {
        return new Node\Stmt\Expression(
            expr: new Node\Expr\Assign(
                var: new Node\Expr\Variable('dispatcher'),
                expr: new Node\Expr\New_(
                    class: new Node\Name('Dispatcher'),
                    args: [
                        new Node\Arg(
                            value: new Node\Expr\Array_(
                                items: array_filter([
                                    new Node\Expr\ArrayItem(
                                        new Node\Expr\New_(
                                            new Node\Name('Uuid'),
                                        ),
                                    ),
                                    new Node\Expr\ArrayItem(
                                        value: new Node\Expr\New_(
                                            class: new Node\Name('BasePath'),
                                            args: [
                                                new Node\Arg(
                                                    new Node\Scalar\String_($this->config['path'] ?? '/')
                                                ),
                                            ]
                                        ),
                                    ),
                                    \array_key_exists('authorization', $this->config['http_hook']) ? new Node\Expr\ArrayItem(
                                        (new Satellite\Runtime\Authorization())->build($this->config['http_hook']),
                                    ) : null,
                                    new Node\Expr\ArrayItem(
                                        value: new Node\Expr\Closure(
                                            subNodes: [
                                                'params' => [
                                                    new Node\Param(
                                                        var: new Node\Expr\Variable('request'),
                                                        type: new Node\Name\FullyQualified(\Psr\Http\Message\RequestInterface::class),
                                                    ),
                                                ],
                                                'uses' => [
                                                    new Node\Expr\Variable('runtime'),
                                                    new Node\Expr\Variable('psr17Factory'),
                                                ],
                                                'stmts' => $this->buildDispatcherClosure($builder),
                                            ],
                                        ),
                                    ),
                                ]),
                                attributes: [
                                    'kind' => Node\Expr\Array_::KIND_SHORT,
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        );
    }

    private function buildDispatcherClosure(Builder $builder)
    {
        return [
            new Node\Stmt\Expression(
                expr: new Node\Expr\Assign(
                    var: new Node\Expr\Variable('interpreter'),
                    expr: new Node\Expr\New_(new Node\Name(\Symfony\Component\ExpressionLanguage\ExpressionLanguage::class)),
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
                                new Node\Scalar\String_($this->config['http_hook']['expression'])
                            ),
                            new Node\Arg(
                                new Node\Expr\Array_([
                                    new Node\Expr\ArrayItem(
                                        value: new Node\Expr\FuncCall(
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
                                                ),
                                            ]
                                        ),
                                        key: new Node\Scalar\String_('input'),
                                    ),
                                ])
                            ),
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
                                        new Node\Expr\Variable('item')
                                    ),
                                ]
                            ),
                        ),
                    ],
                ]
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('response'),
                    new Node\Expr\MethodCall(
                        var: new Node\Expr\Variable('runtime'),
                        name: 'run'
                    )
                )
            ),
            $builder->getNode(),
        ];
    }
}
