<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Kiboko\Component\Satellite\Cloud\Command\Command;

final class RemovePipelineStepCommand implements Command
{
    public function __construct(public string $pipeline, public string $code)
    {}
}
