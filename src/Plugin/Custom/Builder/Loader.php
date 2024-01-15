<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;

    public function __construct(private Node\Expr $service, private readonly string $containerNamespace)
    {
    }

    public function withService(Node\Expr $service): self
    {
        $this->service = $service;

        return $this;
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
                class: new Node\Name\FullyQualified($this->containerNamespace)
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
