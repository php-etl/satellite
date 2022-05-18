<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class State implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct()
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
    }

    public function getNode(): Node\Stmt
    {
        return new Node\Stmt\Nop();
    }

    public function withLogger(Node\Expr $logger): StepBuilderInterface
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): StepBuilderInterface
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): StepBuilderInterface
    {
        $this->state = $state;

        return $this;
    }
}
