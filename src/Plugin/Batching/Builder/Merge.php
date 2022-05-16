<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching\Builder;

use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Merge implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(private int $size)
    {
    }

    public function withLogger(Node\Expr $logger): Merge
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): Merge
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): Merge
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'implements' => [
                        new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\TransformerInterface'),
                        new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\FlushableInterface'),
                    ],
                    'stmts' => [
                        new Node\Stmt\Property(
                            flags: Node\Stmt\Class_::MODIFIER_PRIVATE,
                            props: [
                                new Node\Stmt\PropertyProperty(
                                    name: 'storage'
                                )
                            ],
                            type: new Node\Name('iterable')
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                new Node\Identifier('storage')
                                            ),
                                            new Node\Expr\Array_(
                                                attributes: [
                                                    'kind' => Node\Expr\Array_::KIND_SHORT,
                                                ],
                                            ),
                                        ),
                                    ),
                                ],
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('transform'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            new Node\Expr\PropertyFetch(
                                                new Node\Expr\Variable('this'),
                                                new Node\Identifier('storage')
                                            ),
                                            new Node\Expr\Array_(
                                                attributes: [
                                                    'kind' => Node\Expr\Array_::KIND_SHORT,
                                                ],
                                            ),
                                        ),
                                    ),
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('line'),
                                            expr: new Node\Expr\Yield_(null)
                                        ),
                                    ),
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('count'),
                                            expr: new Node\Scalar\LNumber(1)
                                        ),
                                    ),
                                    new Node\Stmt\While_(
                                        cond: new Node\Expr\ConstFetch(
                                            new Node\Name('true'),
                                        ),
                                        stmts: [
                                            new Node\Stmt\If_(
                                                cond: new Node\Expr\BinaryOp\GreaterOrEqual(
                                                    new Node\Expr\PostInc(
                                                        new Node\Expr\Variable('count'),
                                                    ),
                                                    new Node\Scalar\LNumber($this->size),
                                                ),
                                                subNodes: [
                                                    'stmts' => [
                                                        new Node\Stmt\Expression(
                                                            new Node\Expr\Assign(
                                                                var: new Node\Expr\Variable('count'),
                                                                expr: new Node\Scalar\LNumber(0)
                                                            ),
                                                        ),
                                                        new Node\Stmt\Expression(
                                                            expr: new Node\Expr\Assign(
                                                                var: new Node\Expr\ArrayDimFetch(
                                                                    var: new Node\Expr\PropertyFetch(
                                                                        var: new Node\Expr\Variable('this'),
                                                                        name: new Node\Identifier('storage'),
                                                                    ),
                                                                    dim: null,
                                                                ),
                                                                expr: new Node\Expr\Variable('line'),
                                                            ),
                                                        ),
                                                        new Node\Stmt\Expression(
                                                            expr: new Node\Expr\Assign(
                                                                var: new Node\Expr\Variable('line'),
                                                                expr: new Node\Expr\Yield_(
                                                                    value: new Node\Expr\New_(
                                                                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\AcceptanceResultBucket'),
                                                                        args: [
                                                                            new Node\Arg(
                                                                                new Node\Expr\PropertyFetch(
                                                                                    var: new Node\Expr\Variable('this'),
                                                                                    name: new Node\Identifier('storage'),
                                                                                ),
                                                                            )
                                                                        ],
                                                                    ),
                                                                ),
                                                            ),
                                                        ),
                                                        new Node\Stmt\Expression(
                                                            expr: new Node\Expr\Assign(
                                                                var: new Node\Expr\PropertyFetch(
                                                                    var: new Node\Expr\Variable('this'),
                                                                    name: new Node\Identifier('storage'),
                                                                ),
                                                                expr: new Node\Expr\Array_(
                                                                    attributes: [
                                                                        'kind' => Node\Expr\Array_::KIND_SHORT,
                                                                    ],
                                                                ),
                                                            ),
                                                        ),
                                                    ],
                                                    'else' => new Node\Stmt\Else_(
                                                        stmts: [
                                                            new Node\Stmt\Expression(
                                                                expr: new Node\Expr\Assign(
                                                                    var: new Node\Expr\ArrayDimFetch(
                                                                        var: new Node\Expr\PropertyFetch(
                                                                            var: new Node\Expr\Variable('this'),
                                                                            name: new Node\Identifier('storage'),
                                                                        ),
                                                                        dim: null,
                                                                    ),
                                                                    expr: new Node\Expr\Variable('line'),
                                                                ),
                                                            ),
                                                            new Node\Stmt\Expression(
                                                                new Node\Expr\Assign(
                                                                    var: new Node\Expr\Variable('line'),
                                                                    expr: new Node\Expr\Yield_(
                                                                        value: new Node\Expr\New_(
                                                                            class: new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\EmptyResultBucket')
                                                                        ),
                                                                    ),
                                                                ),
                                                            ),
                                                        ],
                                                    ),
                                                ],
                                            ),
                                        ],
                                    ),
                                ],
                                'returnType' => new Node\Name\FullyQualified('Generator'),
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('flush'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Return_(
                                        expr: new Node\Expr\New_(
                                            class: new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\AcceptanceResultBucket'),
                                            args: [
                                                new Node\Arg(
                                                    new Node\Expr\PropertyFetch(
                                                        var: new Node\Expr\Variable('this'),
                                                        name: new Node\Identifier('storage'),
                                                    ),
                                                )
                                            ],
                                        )
                                    )
                                ],
                                'returnType' => new Node\Name\FullyQualified('Kiboko\\Contract\\Bucket\\ResultBucketInterface'),
                            ],
                        ),
                    ],
                ],
            ),
        );
    }
}
