<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Builder;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class Reject implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;
    /** @var list<?Node\Expr> */
    private array $exclusions = [];

    public function __construct()
    {}

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

    public function withExclusions(Node\Expr ...$exclusions): self
    {
        array_push($this->exclusions, ...$exclusions);

        return $this;
    }

    private function buildExclusions(Node\Expr ...$exclusions): Node\Expr
    {
        if (count($exclusions) > 2) {
            $left = array_pop($exclusions);
            return new Node\Expr\BinaryOp\LogicalAnd(
                $left,
                $this->buildExclusions(...$exclusions),
            );
        }

        if (count($exclusions) > 1) {
            $left = array_pop($exclusions);
            $right = array_pop($exclusions);
            return new Node\Expr\BinaryOp\LogicalAnd(
                $left,
                $right,
            );
        }

        if (count($exclusions) > 0) {
            return array_pop($exclusions);
        }

        return new Node\Expr\ConstFetch(
            new Node\Name('true'),
        );
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(null, [
                'implements' => [
                    new Node\Name\FullyQualified(TransformerInterface::class),
                ],
                'stmts' => [
                    (new Builder\Method('transform'))
                        ->makePublic()
                        ->setReturnType(new Node\Name\FullyQualified(\Generator::class))
                        ->addStmts([
                            new Node\Stmt\Expression(
                                new Node\Expr\Assign(
                                    new Node\Expr\Variable('line'),
                                    new Node\Expr\Yield_(),
                                )
                            ),
                            new Node\Stmt\While_(
                                new Node\Expr\ConstFetch(
                                    new Node\Name('true'),
                                ),
                                [
                                    new Node\Stmt\If_(
                                        $this->buildExclusions(...$this->exclusions),
                                        [
                                            'stmts' => [
                                                new Node\Stmt\Expression(
                                                    new Node\Expr\Assign(
                                                        new Node\Expr\Variable('line'),
                                                        new Node\Expr\Yield_(
                                                            new Node\Expr\New_(
                                                                new Node\Name\FullyQualified(RejectionResultBucket::class),
                                                                [
                                                                    new Node\Arg(new Node\Expr\Variable('line')),
                                                                ]
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                                new Node\Stmt\Continue_(),
                                            ]
                                        ]
                                    ),
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            new Node\Expr\Variable('line'),
                                            new Node\Expr\Yield_(
                                                new Node\Expr\New_(
                                                    new Node\Name\FullyQualified(AcceptanceResultBucket::class),
                                                    [
                                                        new Node\Arg(new Node\Expr\Variable('line')),
                                                    ]
                                                ),
                                            ),
                                        ),
                                    ),
                                ],
                            ),
                            new Node\Stmt\Expression(
                                new Node\Expr\Yield_(
                                    new Node\Expr\Variable('line')
                                ),
                            ),
                        ])
                        ->getNode()
                ],
            ])
        );
    }
}
