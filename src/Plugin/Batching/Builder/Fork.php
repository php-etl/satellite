<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Fork implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(
        private Node\Expr $foreach,
        private Node\Expr $do,
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
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

    public function withForeach(Node\Expr $foreach): self
    {
        $this->foreach = $foreach;

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
                    ],
                    'stmts' => [
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('transform'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_(null)
                                        ),
                                    ),
                                    new Node\Stmt\Do_(
                                        cond: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_(
                                                new Node\Expr\New_(
                                                    class: new Node\Name\FullyQualified(
                                                        name: 'Kiboko\\Component\\Bucket\\AcceptanceResultBucket'
                                                    ),
                                                    args: [
                                                        new Node\Arg(
                                                            value: new Node\Expr\Variable('results'),
                                                            unpack: true
                                                        ),
                                                    ],
                                                ),
                                            )
                                        ),
                                        stmts: [
                                            new Node\Stmt\Expression(
                                                expr: new Node\Expr\Assign(
                                                    var: new Node\Expr\Variable('results'),
                                                    expr: new Node\Expr\Array_(
                                                        attributes: [
                                                            'kind' => Node\Expr\Array_::KIND_SHORT,
                                                        ]
                                                    ),
                                                ),
                                            ),
                                            new Node\Stmt\Foreach_(
                                                expr: $this->foreach,
                                                valueVar: new Node\Expr\Variable('item'),
                                                subNodes: [
                                                    'keyVar' => new Node\Expr\Variable('key'),
                                                    'stmts' => [
                                                        new Node\Stmt\Expression(
                                                            new Node\Expr\Assign(
                                                                new Node\Expr\ArrayDimFetch(
                                                                    new Node\Expr\Variable('results'),
                                                                ),
                                                                $this->do
                                                            ),
                                                        ),
                                                    ],
                                                ],
                                            ),
                                        ]
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
