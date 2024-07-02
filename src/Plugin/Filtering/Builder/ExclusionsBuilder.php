<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Builder;

use PhpParser\Node;

final class ExclusionsBuilder
{
    /** @var list<array{condition: Node\Expr, reason: ?Node\Expr}> */
    private array $exclusions = [];

    public function withCondition(Node\Expr $condition, ?Node\Expr $reason): self
    {
        $this->exclusions[] = [
            'condition' => $condition,
            'reason' => $reason,
        ];

        return $this;
    }

    public function build(): \Generator
    {
        foreach ($this->exclusions as $exclusion) {
            yield new Node\Stmt\If_(
                $exclusion['condition'],
                [
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('input'),
                                new Node\Expr\Yield_(
                                    new Node\Expr\New_(
                                        new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\RejectionResultBucket'),
                                        [
                                            new Node\Arg(
                                                value: $exclusion['reason'],
                                                name: new Node\Identifier('reason')
                                            ),
                                            new Node\Arg(
                                                value: new Node\Expr\ConstFetch(
                                                    name: new Node\Name('null')
                                                ),
                                                name: new Node\Identifier('exception')
                                            ),
                                            new Node\Arg(
                                                value: new Node\Expr\Variable('input'),
                                                name: new Node\Identifier('values')
                                            ),
                                        ]
                                    ),
                                ),
                            ),
                        ),
                        new Node\Stmt\Continue_(),
                    ],
                ]
            );
        }
    }
}
