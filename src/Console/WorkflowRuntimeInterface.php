<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

interface WorkflowRuntimeInterface
{
    public function job(PipelineRuntimeInterface $job): self;
}
