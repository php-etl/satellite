<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Extractor implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(private Node\Expr $service)
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

    public function getNode(): Node
    {
        return new Node\Expr\MethodCall(
            var: new Node\Expr\New_(
                class: new Node\Name\FullyQualified('ProjectExtractorServiceContainer')
            ),
            name: new Node\Identifier('get'),
            args: [
                new Node\Arg(
                    $this->service
                ),
            ]
        );
    }
}
