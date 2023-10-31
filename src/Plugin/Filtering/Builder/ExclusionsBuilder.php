<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Builder;

use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Component\Bucket\RejectionWithReasonResultBucket;
use PhpParser\Builder;
use PhpParser\Node;

final class ExclusionsBuilder implements Builder
{
    /** @var list<list<Node\Expr>> */
    private array $exclusions = [];

    public function withCondition(Node\Expr $condition, ?Node\Expr $reason = null):self
    {
        $this->exclusions[] = [
            'condition' => $condition,
            'reason' => $reason,
        ];

        return $this;
    }

    public function getNode(): Node
    {
        $statements = [];
        foreach ($this->exclusions as $exclusion) {
            $statements[] = new Node\Stmt\If_(
                $exclusion['condition'],
                [
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('input'),
                                new Node\Expr\Yield_(
                                    new Node\Expr\New_(
                                        \array_key_exists('reason', $exclusion) ? new Node\Name\FullyQualified(RejectionWithReasonResultBucket::class) : new Node\Name\FullyQualified(RejectionResultBucket::class),
                                        [
                                            new Node\Arg(new Node\Expr\Variable('input')),
                                            \array_key_exists('reason', $exclusion) ? new Node\Arg($exclusion['reason']) : new Node\Arg(
                                                new Node\Expr\ConstFetch(
                                                    new Node\Name(null)
                                                ),
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

        return new Node;
    }
}
