<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO\Workflow;

use Kiboko\Component\Satellite\Cloud\DTO\JobCode;

final readonly class Action implements JobInterface
{
    public function __construct(
        public string $label,
        public JobCode $code,
        public array $configuration,
        public int $order,
    ) {
    }
}
