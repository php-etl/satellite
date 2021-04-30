<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class StdoutLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function withLogger(Node\Expr $logger): StdoutLoader
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): StdoutLoader
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): StdoutLoader
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Loader\\StdoutLoader'),
        );
    }
}
