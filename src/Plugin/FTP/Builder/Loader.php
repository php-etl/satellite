<?php


namespace Kiboko\Component\Satellite\Plugin\FTP\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;
use PhpParser\Node\Identifier;

class Loader implements StepBuilderInterface
{
    private iterable $servers;
    private iterable $putStatements;
    private array $serversMapping;
    public function __construct()
    {
        $this->servers = [];
        $this->serversMapping = [];
        $this->putStatements = [];
    }

    public function addServerBasePath(string $host, Node\Expr $base_path)
    {
        $this->serversMapping[$host] = $base_path;
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
        array_push($this->servers, array_merge($config, [...$server]));

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
                if ($condition != null) {
                    yield new Node\Stmt\If_(
                        cond: $condition,
                        subNodes: [
                            'stmts' => [
                                ...$this->getPutNode($index, $server, $path, $content, $mode)
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
                                        new Node\Identifier('servers')
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
                                                new Node\Identifier('serversMapping')
                                                ),
                                                dim:
                                                    new Node\Scalar\LNumber($index),
                                            ),

                                        new Node\Scalar\EncapsedStringPart('/'),
                                    ]),
                                    new Node\Expr\FuncCall(
                                        new Node\Name('basename'),
                                        [
                                            new Node\Arg($path)
                                        ]
                                    ),
                                )
                            ),
                            new Node\Arg($content)
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
                                                        items: array_merge(
                                                            [
                                                                new Node\Expr\ArrayItem(
                                                                    value:  new Node\Scalar\String_($server["base_path"]),
                                                                    key: new Node\Scalar\String_('%path%'),
                                                                )
                                                            ],
                                                            [
                                                                new Node\Expr\ArrayItem(
                                                                    value: new Node\Scalar\String_($server["host"]),
                                                                    key:  new Node\Scalar\String_('%server%'),
                                                                )
                                                            ]
                                                        ),
                                                        attributes: [
                                                            'kind' => Node\Expr\Array_::KIND_SHORT,
                                                        ]
                                                    )
                                                )
                                            ]
                                        )
                                    )
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
                                            items: array_merge(
                                                [
                                                new Node\Expr\ArrayItem(
                                                    value:  new Node\Scalar\String_($server["base_path"]),
                                                    key: new Node\Scalar\String_('%path%'),
                                                )
                                            ],
                                                [
                                                new Node\Expr\ArrayItem(
                                                    value: new Node\Scalar\String_($server["host"]),
                                                    key:  new Node\Scalar\String_('%server%'),
                                                )
                                            ]
                                            ),
                                            attributes: [
                                                'kind' => Node\Expr\Array_::KIND_SHORT,
                                            ]
                                        )
                                    )
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
                        new Node\Stmt\Property(
                            flags: 4,
                            props: [
                                new Node\Stmt\PropertyProperty(
                                    new Node\Name('logger')
                                )
                            ],
                            type: new Node\Name\FullyQualified('Psr\Log\LoggerInterface')
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\PropertyFetch(
                                                var: new Node\Expr\Variable('this'),
                                                name: new Identifier('logger')
                                            ),
                                            expr: new Node\Expr\New_(
                                                class: new Node\Name\FullyQualified('Psr\Log\NullLogger')
                                            )
                                        )
                                    ),
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\PropertyFetch(
                                                var: new Node\Expr\Variable('this'),
                                                name: new Identifier('serversMapping')
                                            ),
                                            expr: new Node\Expr\Array_(
                                                items: [
                                                    ...$this->compileServersMapping()
                                                ],
                                                attributes: [
                                                    'kind' => Node\Expr\Array_::KIND_SHORT
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
                                                        new Node\Identifier('servers')
                                                    ),
                                                    new Node\Scalar\LNumber($index),
                                                ),
                                                $server[0]
                                            )
                                        ),
                                        $this->servers,
                                        array_keys($this->servers),
                                    )
                                ],
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('load'),
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
                                                        class: new Node\Name\FullyQualified('Kiboko\Component\Bucket\ComplexResultBucket')
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
                                                        )
                                                    ]
                                                )
                                            ),
                                        ],
                                    ),
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Yield_(
                                            value: new Node\Expr\Variable('bucket')
                                        ),
                                    ),
                                ],
                                'returnType' => new Node\Name\FullyQualified('Generator'),
                            ],
                        ),
                    ],
                ],
            ),
        );
    }
}
