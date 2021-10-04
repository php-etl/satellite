<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $service;
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

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
            var: new Node\Expr\MethodCall(
                var: new Node\Expr\Variable('runtime'),
                name: new Node\Name('container')
            ),
            name: new Node\Name('get'),
            args: [
                new Node\Arg(
                    value: $this->service
                )
            ]
        );
    }
}
