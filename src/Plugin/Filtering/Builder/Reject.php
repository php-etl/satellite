<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class Reject implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;

    private ?string $reason = null;

    /** @var list<?Node\Expr> */
    private array $exclusions = [];

    public function __construct()
    {
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

    public function withExclusions(Node\Expr ...$exclusions): self
    {
        array_push($this->exclusions, ...$exclusions);

        return $this;
    }

    public function withReason(string $reason): self
    {
        $this->reason = $reason;

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
                    new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\TransformerInterface'),
                ],
                'stmts' => [
                    (new Builder\Method('transform'))
                        ->makePublic()
                        ->setReturnType(new Node\Name\FullyQualified('Generator'))
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
                                                                class: new Node\Name\FullyQualified(
                                                                    \Kiboko\Component\Bucket\RejectionResultBucket::class
                                                                ),
                                                                args: [
                                                                    new Node\Arg(
                                                                        $this->reason !== null
                                                                            ? new Node\Scalar\String_($this->reason)
                                                                            : new Node\Expr\ConstFetch(new Node\Name('null')),
                                                                    ),
                                                                    new Node\Arg(
                                                                        new Node\Expr\ConstFetch(new Node\Name('null')),
                                                                    ),
                                                                    new Node\Arg(
                                                                        new Node\Expr\Variable('input'),
                                                                    ),
                                                                ],
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
                                                    new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\AcceptanceResultBucket'),
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
