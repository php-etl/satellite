<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO\Autoload;
use Kiboko\Component\Satellite\Cloud\DTO\ProjectId;
use Kiboko\Component\Satellite\Cloud\DTO\StepList;

final class DeclarePipelineCommand implements Command
{
    public function __construct(
        public string $label,
        public string $code,
        public ProjectId $project,
        public StepList $stepList,
        public ?Autoload $autoload = null,
    ) {}
}
