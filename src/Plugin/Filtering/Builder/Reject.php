<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Builder;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Component\Bucket\RejectionWithReasonResultBucket;
use Kiboko\Component\Satellite\Plugin\Filtering\DTO\Exclusion;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class Reject implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;
    /** @var list<?Exclusion> */
    private array $exclusions = [];

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

    public function withExclusions(Exclusion ...$exclusions): self
    {
        array_push($this->exclusions, ...$exclusions);

        return $this;
    }

    private function buildExclusions(Exclusion ...$exclusions): array
    {
        $statements = [];
        foreach ($exclusions as $exclusion) {
            $statements[] = new Node\Stmt\If_(
                $exclusion->when,
                [
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\Assign(
                                new Node\Expr\Variable('input'),
                                new Node\Expr\Yield_(
                                    new Node\Expr\New_(
                                        $exclusion->reason ? new Node\Name\FullyQualified(RejectionWithReasonResultBucket::class) : new Node\Name\FullyQualified(RejectionResultBucket::class),
                                        [
                                            new Node\Arg(new Node\Expr\Variable('input')),
                                            $exclusion->reason ? new Node\Arg($exclusion->reason) : new Node\Arg(
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

        return $statements;
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
                                    ...$this->buildExclusions(...$this->exclusions),
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
