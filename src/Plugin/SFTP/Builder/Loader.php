<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    private iterable $servers;
    private iterable $putStatements;

    public function __construct(private ExpressionLanguage $interpreter)
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->servers = [];
        $this->putStatements = [];
    }

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): self
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): self
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
                            name: new Node\Identifier('__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => array_map(
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
                                ),
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

    private function getPutNode($index, $server, $path, $content, $mode): array
    {
        return [
            new Node\Stmt\Expression(
                new Node\Expr\FuncCall(
                    name: new Node\Name('ssh2_sftp_mkdir'),
                    args: [
                        new Node\Arg(
                            new Node\Expr\ArrayDimFetch(
                                var: new Node\Expr\PropertyFetch(
                                    var: new Node\Expr\Variable('this'),
                                    name: new Node\Identifier('servers'),
                                ),
                                dim: new Node\Scalar\LNumber($index)
                            ),
                        ),
                        new Node\Arg(
                            new Node\Expr\FuncCall(
                                name: new Node\Name('dirname'),
                                args: [
                                    new Node\Arg(
                                        value: new Node\Expr\BinaryOp\Concat(
                                            new Node\Scalar\Encapsed([
                                                new Node\Scalar\EncapsedStringPart($server["base_path"]),
                                                new Node\Scalar\EncapsedStringPart('/'),
                                            ]),
                                            $path,
                                        )
                                    )
                                ]
                            ),
                        ),
                        new Node\Arg(
                            new Node\Expr\FuncCall(
                                name: new Node\Name('octdec'),
                                args: [
                                    $mode,
                                ],
                            ),
                        ),
                        new Node\Arg(
                            new Node\Expr\ConstFetch(
                                name: new Node\Name('true')
                            ),
                        ),
                    ]
                )
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    var: new Node\Expr\Variable('stream'),
                    expr: new Node\Expr\FuncCall(
                        name: new Node\Name('fopen'),
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\BinaryOp\Concat(
                                    new Node\Expr\BinaryOp\Concat(
                                        new Node\Expr\BinaryOp\Concat(
                                            new Node\Scalar\String_('ssh2.sftp://'),
                                            new Node\Expr\FuncCall(
                                                name: new Node\Name('intval'),
                                                args: [
                                                    new Node\Arg(
                                                        new Node\Expr\ArrayDimFetch(
                                                            var: new Node\Expr\PropertyFetch(
                                                                var: new Node\Expr\Variable('this'),
                                                                name: new Node\Identifier('servers'),
                                                            ),
                                                            dim: new Node\Scalar\LNumber($index)
                                                        ),
                                                    ),
                                                ]
                                            )
                                        ),
                                        new Node\Scalar\Encapsed([
                                            new Node\Scalar\EncapsedStringPart($server["base_path"]),
                                            new Node\Scalar\EncapsedStringPart('/'),
                                        ])
                                    ),
                                    $path,
                                ),
                            ),
                            new Node\Arg(new Node\Scalar\String_('w')),
                        ],
                    ),
                ),
            ),
            new Node\Stmt\If_(
                cond: new Node\Expr\BinaryOp\Identical(
                    left: new Node\Expr\FuncCall(
                        name: new Node\Name('stream_copy_to_stream'),
                        args: [
                            new Node\Arg($content),
                            new Node\Arg(new Node\Expr\Variable('stream')),
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
                        )
                    ],
                ],
            ),
            new Node\Stmt\Expression(
                expr: new Node\Expr\FuncCall(
                    name: new Node\Name('fclose'),
                    args: [
                        new Node\Arg($content),
                    ]
                )
            ),
            new Node\Stmt\Expression(
                expr: new Node\Expr\FuncCall(
                    name: new Node\Name('fclose'),
                    args: [
                        new Node\Arg(new Node\Expr\Variable('stream')),
                    ]
                )
            ),
        ];
    }
}
