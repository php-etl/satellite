<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class JSONStreamLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $rejection = null;
    private ?Node\Expr $state = null;

    public function __construct(private readonly string $stream)
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
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\Component\Pipeline\Loader\JSONStreamLoader'),
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
