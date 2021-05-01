<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class CustomBuilder implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(private string $className)
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
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

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            new Node\Name\FullyQualified($this->className),
        );
    }
}
