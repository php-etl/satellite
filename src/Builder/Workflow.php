<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Workflow implements Builder
{
    private array $jobs = [];

    public function addJob(Node\Expr|Builder $job): self
    {
        array_push($this->jobs, $job);

        return $this;
    }

    public function getNode(): Node
    {
        $workflow = [];

        foreach ($this->jobs as $job) {
            $workflow[] = new Node\Arg(
                $job->getNode()
            );
        }

        return new Node\Stmt\Expression(
            new Node\Expr\MethodCall(
                var: new Node\Expr\New_(
                    class: new Node\Name\FullyQualified('Kiboko\Component\Workflow\Workflow'),
                    args: $workflow
                ),
                name: new Node\Name('run')
            )
        );
    }
}
