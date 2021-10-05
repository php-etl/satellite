<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\SchedulingInterface;
use Psr\Container\ContainerInterface;

interface WorkflowRuntimeInterface extends SchedulingInterface, RunnableInterface
{
    public function container(): ContainerInterface;
}
