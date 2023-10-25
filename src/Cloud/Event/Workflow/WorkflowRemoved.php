<?php

namespace Kiboko\Component\Satellite\Cloud\Event\Workflow;

class WorkflowRemoved
{
    public function __construct(
        private readonly string $id,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }
}
