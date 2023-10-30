<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Builder;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Component\Bucket\RejectionWithReasonResultBucket;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use PhpParser\Builder;
use PhpParser\Node;

final class Reject implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;
    private ?ExclusionsBuilder $exclusions = null;

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

    public function withExclusions(ExclusionsBuilder $builder): self
    {
        $this->exclusions = $builder;

        return $this;
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
                                    ...$this->exclusions->getNode(),
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
