<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class DebugLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function __construct(private string $stream)
    {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
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
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Loader\\DebugLoader'),
            args: [
                new Node\Arg(
                    value: new Node\Expr\FuncCall(
                        name: new Node\Name\FullyQualified('fopen'),
                        args: [
                            new Node\Arg(
                                new Node\Scalar\String_($this->stream),
                            ),
                            new Node\Arg(
                                new Node\Scalar\String_('wb'),
                            ),
                        ],
                    ),
                ),
            ],
        );
    }
}
