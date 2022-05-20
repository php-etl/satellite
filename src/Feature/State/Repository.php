<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Kiboko\Contract\Configurator;
use PhpParser\Node;

final class Repository implements Configurator\StepRepositoryInterface
{
    use RepositoryTrait;

    private ?Node\Expr $logger;

    public function __construct(private Builder\State $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getBuilder(): Builder\State
    {
        return $this->builder;
    }
}
