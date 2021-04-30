<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class StderrLoader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function withLogger(Node\Expr $logger): StderrLoader
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): StderrLoader
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): StderrLoader
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Loader\\StderrLoader'),
        );
    }
}
