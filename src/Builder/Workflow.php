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
        array_push($this->jobs, function (Node\Expr $workflow) use ($job) {
            return new Node\Expr\MethodCall(
                var: $workflow,
                name: new Node\Identifier('extract'),
                args: [
                    new Node\Arg($job instanceof Builder ? $job->getNode() : $job)
                ]
            );
        });

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $workflow = new Node\Expr\New_(
            new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Pipeline'),
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\PipelineRunner'),
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified('Psr\\Log\\NullLogger'),
                                )
                            )
                        ],
                    ),
                ),
            ],
        );

        foreach ($this->jobs as $job) {
            $workflow = $job($workflow);
        }

        return $workflow;
    }
}
