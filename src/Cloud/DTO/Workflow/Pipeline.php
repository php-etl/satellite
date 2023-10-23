<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO\Workflow;

use Kiboko\Component\Satellite\Cloud\DTO\JobCode;
use Kiboko\Component\Satellite\Cloud\DTO\StepList;

final readonly class Pipeline implements JobInterface
{
   public function __construct(
       public string $label,
       public JobCode $code,
       public StepList $stepList,
       public int $order,
   ) {
   }
}
