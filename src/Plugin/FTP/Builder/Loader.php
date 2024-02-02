<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\FTP\Builder;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;
    private iterable $servers = [];
    private iterable $putStatements = [];
    private array $serversMapping = [];

    public function __construct(private readonly ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage())
    {
    }

    public function addServerBasePath(Node\Expr $base_path): void
    {
        $this->serversMapping[] = $base_path;
    }

    private function compileServersMapping(): array
    {
        $output = [];
        foreach ($this->serversMapping as $basePath) {
            $output[] = new Node\Expr\ArrayItem(
                value: $basePath,
            );
        }

        return $output;
    }

    public function withLogger(Node\Expr $logger): StepBuilderInterface
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): StepBuilderInterface
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): StepBuilderInterface
    {
        $this->state = $state;

        return $this;
    }

    public function withServer(array $config, Node ...$server): self
    {
        $this->servers[] = array_merge($config, [...$server]);

        return $this;
    }

    public function withPut(
        Node\Expr $path,
        Node\Expr $content,
        ?Node\Expr $mode,
        ?Node\Expr $condition,
    ): self {
        $this->putStatements[] = [$path, $content, $mode, $condition];

        return $this;
    }

    private function compilePutStatements(): iterable
    {
        foreach ($this->putStatements as [$path, $content, $mode, $condition]) {
            foreach ($this->servers as $index => $server) {
                if (null != $condition) {
                    yield new Node\Stmt\If_(
                        cond: $condition,
                        subNodes: [
                            'stmts' => [
                                ...$this->getPutNode($index, $server, $path, $content, $mode),
                            ],
                        ]
                    );
                } else {
                    yield from $this->getPutNode($index, $server, $path, $content, $mode);
                }
            }
        }
    }

    private function getPutNode($index, $server, $path, $content, $mode): array
    {
        return [
            new Node\Stmt\If_(
                cond: new Node\Expr\BinaryOp\Identical(
                    left: new Node\Expr\FuncCall(
                        name: new Node\Name('ftp_fput'),
                        args: [
                            new Node\Arg(
                                new Node\Expr\ArrayDimFetch(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        new Identifier('servers')
                                    ),
                                    new Node\Scalar\LNumber($index),
                                ),
                            ),
                            new Node\Arg(
                                value: new Node\Expr\BinaryOp\Concat(
                                    new Node\Scalar\Encapsed([
                                        new Node\Expr\ArrayDimFetch(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                new Identifier('serversMapping')
                                            ),
                                            dim: new Node\Scalar\LNumber($index),
                                        ),

                                        new Node\Scalar\EncapsedStringPart('/'),
                                    ]),
                                    new Node\Expr\FuncCall(
                                        new Node\Name('basename'),
                                        [
                                            new Node\Arg($path),
                                        ]
                                    ),
                                )
                            ),
                            new Node\Arg($content),
                        ],
                    ),
                    right: new Node\Expr\ConstFetch(
                        name: new Node\Name('false')
                    ),
                ),
                subNodes: [
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\MethodCall(
                                var: new Node\Expr\PropertyFetch(
                                    var: new Node\Expr\Variable('this'),
                                    name: new Node\Name('logger'),
                                ),
                                name: new Node\Name('alert'),
                                args: [
                                    new Node\Arg(
                                        new Node\Expr\FuncCall(
                                            name: new Node\Name('strtr'),
                                            args: [
                                                new Node\Arg(
                                                    new Node\Scalar\String_('Error while uploading file "%path%" to "%server%" server')
                                                ),
                                                new Node\Arg(
                                                    new Node\Expr\Array_(
                                                        items: [new Node\Expr\ArrayItem(
                                                            value: compileValueWhenExpression($this->interpreter, $server['base_path']),
                                                            key: new Node\Scalar\String_('%path%'),
                                                        ), new Node\Expr\ArrayItem(
                                                            value: compileValueWhenExpression($this->interpreter, $server['host']),
                                                            key: new Node\Scalar\String_('%server%'),
                                                        )],
                                                        attributes: [
                                                            'kind' => Node\Expr\Array_::KIND_SHORT,
                                                        ]
                                                    )
                                                ),
                                            ]
                                        )
                                    ),
                                ]
                            )
                        ),
                        new Node\Stmt\Expression(
                            new Node\Expr\MethodCall(
                                var: new Node\Expr\Variable('bucket'),
                                name: new Node\Name('reject'),
                                args: [
                                    new Node\Arg(
                                        new Node\Expr\Array_(
                                            items: [new Node\Expr\ArrayItem(
                                                value: compileValueWhenExpression($this->interpreter, $server['base_path']),
                                                key: new Node\Scalar\String_('%path%'),
                                            ), new Node\Expr\ArrayItem(
                                                value: compileValueWhenExpression($this->interpreter, $server['host']),
                                                key: new Node\Scalar\String_('%server%'),
                                            )],
                                            attributes: [
                                                'kind' => Node\Expr\Array_::KIND_SHORT,
                                            ]
                                        )
                                    ),
                                ]
                            )
                        ),
                    ],
                ],
            ),
        ];
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'implements' => [
                        new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\LoaderInterface'),
                    ],
                    'stmts' => [
                        new Node\Stmt\ClassMethod(
                            name: new Identifier('__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'params' => [
                                    new Node\Param(
                                        var: new Node\Expr\Variable('logger'),
                                        type: new Node\Name\FullyQualified(name: 'Psr\\Log\\LoggerInterface'),
                                        flags: Node\Stmt\Class_::MODIFIER_PRIVATE,
                                    ),
                                ],
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\PropertyFetch(
                                                var: new Node\Expr\Variable('this'),
                                                name: new Identifier('serversMapping')
                                            ),
                                            expr: new Node\Expr\Array_(
                                                items: [
                                                    ...$this->compileServersMapping(),
                                                ],
                                                attributes: [
                                                    'kind' => Node\Expr\Array_::KIND_SHORT,
                                                ]
                                            )
                                        )
                                    ),
                                    ...array_map(
                                        fn ($server, $index) => new Node\Stmt\Expression(
                                            new Node\Expr\Assign(
                                                new Node\Expr\ArrayDimFetch(
                                                    new Node\Expr\PropertyFetch(
                                                        new Node\Expr\Variable('this'),
                                                        new Identifier('servers')
                                                    ),
                                                    new Node\Scalar\LNumber($index),
                                                ),
                                                $server[0]
                                            )
                                        ),
                                        $this->servers,
                                        array_keys($this->servers),
                                    ),
                                ],
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Identifier('load'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_(),
                                        ),
                                    ),
                                    new Node\Stmt\Do_(
                                        cond: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_(
                                                value: new Node\Expr\Variable('bucket'),
                                            ),
                                        ),
                                        stmts: [
                                            new Node\Stmt\Expression(
                                                new Node\Expr\Assign(
                                                    var: new Node\Expr\Variable('bucket'),
                                                    expr: new Node\Expr\New_(
                                                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\ComplexResultBucket')
                                                    )
                                                )
                                            ),
                                            ...$this->compilePutStatements(),
                                            new Node\Stmt\Expression(
                                                new Node\Expr\MethodCall(
                                                    var: new Node\Expr\Variable('bucket'),
                                                    name: new Node\Name('accept'),
                                                    args: [
                                                        new Node\Arg(
                                                            new Node\Expr\Variable('input'),
                                                        ),
                                                    ]
                                                )
                                            ),
                                        ],
                                    ),
                                    ...$this->compileCloseServers(),
                                ],
                                'returnType' => new Node\Name\FullyQualified('Generator'),
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Identifier('createDirectories'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'returnType' => new Node\Expr\ConstFetch(new Node\Name('void')),
                                'params' => [
                                    new Node\Param(
                                        var: new Node\Expr\Variable('ftpcon')
                                    ),
                                    new Node\Param(
                                        var: new Node\Expr\Variable('baseDir'),
                                        type: new Identifier('string')
                                    ),
                                    new Node\Param(
                                        var: new Node\Expr\Variable('path'),
                                        type: new Identifier('string')
                                    ),
                                    new Node\Param(
                                        var: new Node\Expr\Variable('mode'),
                                        default: new Node\Expr\ConstFetch(new Node\Name('null')),
                                        type: new Identifier('string')
                                    ),
                                ],
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\FuncCall(
                                            name: new Node\Name('ftp_chdir'),
                                            args: [
                                                new Node\Arg(
                                                    new Node\Expr\Variable('ftpcon')
                                                ),
                                                new Node\Arg(
                                                    new Node\Expr\Variable('baseDir')
                                                ),
                                            ],
                                        ),
                                    ),
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('directories'),
                                            expr: new Node\Expr\FuncCall(
                                                name: new Node\Name('explode'),
                                                args: [
                                                    new Node\Arg(
                                                        value: new Node\Expr\ConstFetch(
                                                            name: new Node\Name('DIRECTORY_SEPARATOR')
                                                        )
                                                    ),
                                                    new Node\Arg(
                                                        value: new Node\Expr\FuncCall(
                                                            name: new Node\Name('dirname'),
                                                            args: [
                                                                new Node\Arg(
                                                                    new Node\Expr\Variable('path')
                                                                ),
                                                            ],
                                                        ),
                                                    ),
                                                ],
                                            ),
                                        ),
                                    ),
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('actualDirectory'),
                                            expr: new Node\Expr\ConstFetch(
                                                name: new Node\Name('DIRECTORY_SEPARATOR')
                                            ),
                                        ),
                                    ),
                                    new Node\Stmt\Foreach_(
                                        expr: new Node\Expr\Variable('directories'),
                                        valueVar: new Node\Expr\Variable('directory'),
                                        subNodes: [
                                            'stmts' => [
                                                new Node\Stmt\Expression(
                                                    expr: new Node\Expr\Assign(
                                                        var: new Node\Expr\Variable('actualDirectory'),
                                                        expr: new Node\Expr\BinaryOp\Concat(
                                                            left: new Node\Expr\Variable('actualDirectory'),
                                                            right: new Node\Expr\BinaryOp\Concat(
                                                                left: new Node\Expr\Variable('directory'),
                                                                right: new Node\Expr\ConstFetch(
                                                                    name: new Node\Name('DIRECTORY_SEPARATOR')
                                                                )
                                                            )
                                                        )
                                                    )
                                                ),
                                                new Node\Stmt\If_(
                                                    cond: new Node\Expr\BooleanNot(
                                                        expr: new Node\Expr\FuncCall(
                                                            name: new Node\Name('ftp_nlist'),
                                                            args: [
                                                                new Node\Arg(
                                                                    new Node\Expr\Variable('ftpcon')
                                                                ),
                                                                new Node\Arg(
                                                                    new Node\Expr\Variable('actualDirectory')
                                                                ),
                                                            ],
                                                        ),
                                                    ),
                                                    subNodes: [
                                                        'stmts' => [
                                                            new Node\Stmt\If_(
                                                                cond: new Node\Expr\BooleanNot(
                                                                    expr: new Node\Expr\FuncCall(
                                                                        name: new Node\Name('ftp_nlist'),
                                                                        args: [
                                                                            new Node\Arg(
                                                                                new Node\Expr\Variable('ftpcon')
                                                                            ),
                                                                            new Node\Arg(
                                                                                new Node\Expr\Variable('directory')
                                                                            ),
                                                                        ],
                                                                    ),
                                                                ),
                                                                subNodes: [
                                                                    'stmts' => [
                                                                        new Node\Stmt\Expression(
                                                                            new Node\Expr\FuncCall(
                                                                                name: new Node\Name('ftp_mkdir'),
                                                                                args: [
                                                                                    new Node\Arg(
                                                                                        new Node\Expr\Variable('ftpcon')
                                                                                    ),
                                                                                    new Node\Arg(
                                                                                        new Node\Expr\Variable('directory')
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                        ),
                                                                    ],
                                                                ],
                                                            ),
                                                            new Node\Stmt\If_(
                                                                cond: new Node\Expr\BinaryOp\NotIdentical(
                                                                    left: new Node\Expr\Variable('mode'),
                                                                    right: new Node\Expr\ConstFetch(new Node\Name('null'))
                                                                ),
                                                                subNodes: [
                                                                    'stmts' => [
                                                                        new Node\Stmt\Expression(
                                                                            new Node\Expr\FuncCall(
                                                                                name: new Node\Name('ftp_chmod'),
                                                                                args: [
                                                                                    new Node\Arg(
                                                                                        new Node\Expr\Variable('ftpcon')
                                                                                    ),
                                                                                    new Node\Arg(
                                                                                        new Node\Expr\FuncCall(
                                                                                            name: new Node\Name('octdec'),
                                                                                            args: [
                                                                                                new Node\Arg(
                                                                                                    new Node\Expr\Variable('mode')
                                                                                                ),
                                                                                            ]
                                                                                        )
                                                                                    ),
                                                                                    new Node\Arg(
                                                                                        new Node\Expr\Variable('directory')
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                        ),
                                                                    ],
                                                                ],
                                                            ),
                                                            new Node\Stmt\Expression(
                                                                expr: new Node\Expr\FuncCall(
                                                                    name: new Node\Name('ftp_chdir'),
                                                                    args: [
                                                                        new Node\Arg(
                                                                            new Node\Expr\Variable('ftpcon')
                                                                        ),
                                                                        new Node\Arg(
                                                                            new Node\Expr\Variable('directory')
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
                                ],
                            ],
                        ),
                    ],
                ],
            ),
            args: [
                new Node\Arg(value: $this->logger ?? new Node\Expr\New_(new Node\Name\FullyQualified('Psr\\Log\\NullLogger'))),
            ]
        );
    }

    public function compileCloseServers(): array
    {
        $output = [];

        foreach ($this->servers as $key => $server) {
            $output[] = new Node\Stmt\Expression(
                expr: new Node\Expr\FuncCall(
                    name: new Node\Name('ftp_close'),
                    args: [
                        new Node\Arg(
                            new Node\Expr\ArrayDimFetch(
                                var: new Node\Expr\PropertyFetch(
                                    var: new Node\Expr\Variable('this'),
                                    name: new Identifier('servers')
                                ),
                                dim: new Node\Scalar\LNumber($key),
                            ),
                        ),
                    ]
                )
            );
        }

        return $output;
    }
}
