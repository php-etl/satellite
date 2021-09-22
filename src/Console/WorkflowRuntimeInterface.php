<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Component\Workflow\SchedulingInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;

interface WorkflowRuntimeInterface extends SchedulingInterface, RunnableInterface
{
}
