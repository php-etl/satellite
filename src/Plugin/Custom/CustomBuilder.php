<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class CustomBuilder implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;

    public function __construct(private readonly string $className)
    {
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
