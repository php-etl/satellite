<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Contract\Pipeline\ExtractingInterface;
use Kiboko\Contract\Pipeline\LoadingInterface;
use Kiboko\Contract\Pipeline\RunnableInterface;
use Kiboko\Contract\Pipeline\TransformingInterface;

interface PipelineRuntimeInterface extends ExtractingInterface, TransformingInterface, LoadingInterface, RunnableInterface
{
}
