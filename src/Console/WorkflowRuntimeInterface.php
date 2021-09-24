<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\SchedulingInterface;

interface WorkflowRuntimeInterface extends SchedulingInterface, RunnableInterface
{
}
