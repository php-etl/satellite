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
    private ?Node\Expr $rejection_serializer = null;
    /** @var list<?Node\Expr> */
    private array $exclusions = [];

    public function __construct() {}

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

    public function withRejectionSerializer(Node\Expr $rejection_serializer): self
    {
        $this->rejection_serializer = $rejection_serializer;

        return $this;
    }

    public function withExclusions(Node\Expr ...$exclusions): self
    {
        array_push($this->exclusions, ...$exclusions);

        return $this;
    }

    private function buildExclusions(Node\Expr ...$exclusions): Node\Expr
    {
        if (\count($exclusions) > 3) {
            $length = \count($exclusions);
            $middle = (int) floor($length / 2);
            $left = \array_slice($exclusions, 0, $middle);
            $right = \array_slice($exclusions, $middle, $length);

            return new Node\Expr\BinaryOp\BooleanAnd(
                $this->buildExclusions(...$left),
                $this->buildExclusions(...$right),
            );
        }

        if (\count($exclusions) > 2) {
            $right = array_shift($exclusions);

            return new Node\Expr\BinaryOp\BooleanAnd(
                $this->buildExclusions(...$exclusions),
                $right,
            );
        }

        if (\count($exclusions) > 1) {
            $left = array_pop($exclusions);
            $right = array_pop($exclusions);

            return new Node\Expr\BinaryOp\BooleanAnd(
                $left,
                $right,
            );
        }

        if (\count($exclusions) > 0) {
            return array_pop($exclusions);
        }

        return new Node\Expr\ConstFetch(
            new Node\Name('false'),
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
                                    new Node\Expr\Variable('input'),
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
                                                        new Node\Expr\Variable('input'),
                                                        new Node\Expr\Yield_(
                                                            new Node\Expr\New_(
                                                                new Node\Name\FullyQualified(RejectionResultBucket::class),
                                                                [
                                                                    null !== $this->rejection_serializer ? new Node\Arg($this->rejection_serializer) : new Node\Arg(new Node\Expr\Variable('input')),
                                                                ]
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                                new Node\Stmt\Continue_(),
                                            ],
                                        ]
                                    ),
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            new Node\Expr\Variable('input'),
                                            new Node\Expr\Yield_(
                                                new Node\Expr\New_(
                                                    new Node\Name\FullyQualified(AcceptanceResultBucket::class),
                                                    [
                                                        new Node\Arg(new Node\Expr\Variable('input')),
                                                    ]
                                                ),
                                            ),
                                        ),
                                    ),
                                ],
                            ),
                            new Node\Stmt\Expression(
                                new Node\Expr\Yield_(
                                    new Node\Expr\Variable('input')
                                ),
                            ),
                        ])
                        ->getNode(),
                ],
            ])
        );
    }
}
