<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console;

use Kiboko\Contract\Pipeline\ExtractingInterface;
use Kiboko\Contract\Pipeline\LoadingInterface;
use Kiboko\Contract\Pipeline\TransformingInterface;

interface RuntimeInterface extends ExtractingInterface, TransformingInterface, LoadingInterface
{
}
